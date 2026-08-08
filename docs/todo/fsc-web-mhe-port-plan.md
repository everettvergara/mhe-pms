# Plan: Port Eagle Eye MHE admin modules → mhe-pms

Status: **in progress** (2026-08-07). Phase 1 geo masters, Phase 4 downtime CRUD, and Eagle Eye downtime **import module** are implemented in `mhe-pms`.

Companion inventory (routes, tables, fields): [fsc-web-mhe-admin-inventory.md](fsc-web-mhe-admin-inventory.md)  
Districts/sites field mapping: [eagle-eye-transfer-readiness.md](eagle-eye-transfer-readiness.md)  
**After port is done:** [fsc-web-mhe-disable-when-live.md](fsc-web-mhe-disable-when-live.md) (hide MHE **Downtime** mod 46 via UI when live; keep dashboard mods 58/101/111)

---

## Standing rules

| Rule | Detail |
|------|--------|
| Working project | **`mhe-pms` only** — all future code/docs writes go here |
| Reference | **`fsc_web` read-only** — never edit Eagle Eye |
| Method | Reimplement in mhe-pms patterns (Laravel 13, Breeze, Bootstrap, policies) — do not copy Eagle Eye files wholesale |
| Domain note | Eagle Eye **MHE Downtime** ≠ mhe-pms **Preventive Maintenance** — related equipment domain, separate workflows |

---

## Goal

Bring Eagle Eye **admin** MHE capabilities into mhe-pms:

1. Masters needed by downtime / units  
2. Downtime transaction (save / post / cancel, attachments, action plans)  
3. Summary / utilization / uptime reports  

**Out of scope:** `/mhe-100` (eliminate), supplier-facing Eagle Eye modules, wholesale UI clone.

---

## Target route sketch (mhe-pms — future)

| Eagle Eye | Proposed mhe-pms (names TBD when coding) |
|-----------|------------------------------------------|
| `/mhes` … | Downtime resource (e.g. `/mhe-downtimes` or `/mhes`) |
| `/mhes/action-plan/*` | Nested or sibling action-plan routes |
| `/mhe-categories` | New master |
| `/mhe-invs` | New master (MHE units/inventory) |
| `/mhe-types` | **Existing** — extend/align only |
| `/districts`, `/sites` | **Existing** — data + optional region/site_type |
| `/regions`, `/site-types` | New masters **if** required for sites/downtime |
| `/mhes/summary\|utilization\|uptime` | Report pages under reports or mhes |
| `/mhe-100` | **Do not add** |

---

## Phased port order

```mermaid
flowchart LR
  p1[Phase1 geo masters] --> p2[Phase2 categories types]
  p2 --> p3[Phase3 inventories]
  p3 --> p4[Phase4 downtime tx]
  p4 --> p5[Phase5 reports]
```

### Phase 1 — Geo masters

- **Done:** Region master + `sites.region_id`; District/Site seeders replaced from SQL dump fixtures (`database/data/*.json`).
- Collision rule applied for `CDI La Union` / `JTI WH1`.
- Site Type still deferred.

### Phase 2 — Categories + types

- Port **MHE categories** (`tb_sf_mf_mhe_category` → new mhe-pms master).  
- Align **MHE types** with existing `MheType` / `/mhe-types`.  
- **Deliverable:** CRUD + seed data matching Eagle Eye codes/names where possible.

### Phase 3 — Inventories (units)

- Port **`tb_sf_mf_mhe_inv`** as MHE unit inventory (maps to Charles todo: type, unit no, site, supplier/provider, etc.).  
- Include import/export behavior as a follow-on if needed.  
- **Deliverable:** master CRUD keyed by `unit_no` + site/type FKs.

### Phase 4 — Downtime transaction

Reimplement from `tb_sf_tr_mhe` + children:

| Capability | Eagle Eye behavior to preserve |
|------------|--------------------------------|
| CRUD list/show/create/edit | `/mhes` resource |
| Save / post / cancel | Status workflow (not separate URLs) |
| Attachments | Files on downtime header |
| Action plans | Child records + optional AP attachments |
| Notify on post | Email/SMS — decide if mhe-pms needs equivalent |

Tables to migrate conceptually: `tb_sf_tr_mhe`, `tb_sf_tr_mhe_attachment`, `tb_sf_tr_mhe_action_plan`, `tb_sf_tr_mhe_action_plan_attachment` (+ status / action-plan status masters as needed).  
Use **restored DB / SQL dump** for columns missing from migrations (`root_cause`, `time_from`/`time_to`, etc.).

#### Phase 4b — Eagle Eye downtime import module (implemented)

Bulk copy from Eagle Eye (`fsc_web` / SQL dump) into mhe-pms downtime tables.

| Piece | Location |
|-------|----------|
| Orchestrator | `app/Services/MheDowntimeImport/MheDowntimeImportOrchestrator.php` |
| Import logic | `app/Services/EagleEye/EagleEyeDowntimeImportService.php` |
| Source readers | JSON seed, live MySQL, SQL dump (`app/Services/MheDowntimeImport/Sources/*`) |
| Export to seed | `php artisan mhe:export-eagle-eye-seed` |
| Run import | `php artisan mhe:import-downtimes` (+ admin UI `/mhe-downtimes/import`) |

**Tables covered (same four as Eagle Eye):**

| Eagle Eye table | mhe-pms target |
|-----------------|----------------|
| `tb_sf_tr_mhe` | `mhe_downtimes` |
| `tb_sf_tr_mhe_action_plan` | `mhe_downtime_action_plans` |
| `tb_sf_tr_mhe_attachment` | `attachments` (morph on downtime) |
| `tb_sf_tr_mhe_action_plan_attachment` | `attachments` (morph on action plan) |

**Coverage vs `eagleeyefastlogi_fsc_dashboard.sql` (2026-08-07):**

| Entity | In SQL dump | Imported | Notes |
|--------|------------:|---------:|-------|
| Downtimes | 8,584 | **8,584** | 100% of rows present in dump |
| Action plans | 1,852 | **1,847** | 5 orphans excluded — parent downtimes deleted in Eagle Eye (not visible in `fsc_web`) |
| Downtime attachments | 1,066 | **1,055** | 11 skipped (warning) — parent downtime missing from dump |
| AP attachments | 205 | **205** | 100% |

This is **100% of surviving Eagle Eye data** for these tables. Gaps are **deleted parent downtimes** in the source (ids 1, 51, 79, 80, 131–133, 163, 185, etc.), not import bugs.

**Import provisions (built into module):**

| Rule | Behavior |
|------|----------|
| Zero `date_of_incident` / `uptime` | Treat `0000-00-00` and year `< 1970` as null; **COALESCE** `date_of_incident` → `created_at` (log warning); `uptime` stays null |
| Orphan action plans | Skip with **warning** (not error) when parent downtime not found |
| Orphan attachments | Skip with **warning** when parent downtime / action plan not found |
| Legacy attachment files | Store metadata only; `mime_type` / `file_size` nullable; UI shows placeholder when file missing |
| Master FK resolution | Map Eagle Eye site/type/category ids → mhe-pms codes via `ee_*_by_id.json`; fallback site/type/category/user when unmapped |
| Idempotency | `legacy_eagle_eye_id` on downtimes and action plans; re-import is safe (`updateOrCreate`) |

**Follow-on (not yet in exporter):**

- [ ] Auto-prune orphan action plans and attachments during `mhe:export-eagle-eye-seed` (same 5 APs + 11 attachments removed from seed today by hand)
- [ ] Copy physical attachment files from Eagle Eye storage into mhe-pms `storage/` (metadata-only today)

#### Phase 4c — FSC Web user import (implemented, not yet run against production)

Separate from downtime seed import. Lives in `app/Services/FscWebImport/` (`FscUserImportService`, `FscWebImportOrchestrator`) — `php artisan fsc:import`, admin UI checkbox **Users**.

Companion detail: [fsc-web-user-import.md](fsc-web-user-import.md)

**This is not a 100% copy of `tb_sys_mf_user`.** Scope is intentionally narrow (FAST / MHE users only — **not supplier users**):

| Rule | Detail |
|------|--------|
| Source | **Live Eagle Eye MySQL only** (`eagle_eye_mysql`) — not SQL dump, not JSON seed |
| User filter | `is_active = 1` **and** (has **MHE** module access **or** **FA** access type) |
| Role mapping | All imported users → mhe-pms **FAST Administrator**; `is_super_admin` if FA in Eagle Eye |
| Passwords | Bcrypt hash copied as-is via `DB::table` (bypasses `hashed` cast); same login as fsc_web |
| Site scope | Copy `tb_sys_mf_user_site` → `tb_fin_mf_site.code` → mhe-pms `sites.site_code` → `supplier_sites` pivot. District is implicit via each site's `district_id` |
| No EE sites | **Import user only** — no site rows written. Not an error (e.g. EE #559 `amollosa` has MHE Transaction but zero `tb_sys_mf_user_site` rows) |
| Skipped users | Email already taken by a **different** username (`users_skipped`) |
| Not imported | Inactive users; users without MHE/FA access; supplier users; per-module EE permissions; profile photos |
| Purge | `fsc:import` **deletes all local users** first, reseeds `UserSeeder` defaults, then imports |

**Live preview (2026-08-07, local `eagleeyefastlogi_fsc_dashboard`):**

| Metric | Count |
|--------|------:|
| MHE/FA users in scope | 469 |
| Would import | 469 |
| Email conflicts | 0 |
| Site assignments | 9,977 |
| Unmapped site codes | 0 |
| Users imported without sites | 1 (`amollosa` — no rows in Eagle Eye) |

**Open follow-ons:**

- [ ] Skip `UserSeeder` demo admins when `--users` so fsc_web passwords fully prevail on username match
- [ ] Map Eagle Eye access types → distinct mhe-pms roles (optional; flat FAST Administrator today)
- [ ] Run `fsc:import --source=eagle_eye_mysql --users --downtimes --confirm` on go-live

### Phase 5 — Reports

- Dashboard `/mhes/summary`  
- Utilization `/mhes/utilization`  
- Uptime `/mhes/uptime`  

Rebuild queries against mhe-pms schema (do not depend on Eagle Eye MySQL views long-term; re-create equivalent reporting queries/views in mhe-pms if required).

### Explicit skip

- **`/mhe-100`** and `tb_sf_tr_mhe_100` — eliminated.

---

## Data migration notes

1. Prefer live `eagleeyefastlogi_fsc_dashboard` (after restore) over seeders if they diverge.  
2. Map FKs by **business codes** (district code, site code, type code), never raw Eagle Eye ids.  
3. Map `is_active` → mhe-pms `status` (`Active`/`Inactive`).  
4. Attachments: copy files into mhe-pms storage and rewrite paths (metadata import done; file copy pending).  
5. Soft deletes / audit fields: follow mhe-pms conventions even if Eagle Eye lacks them.  
6. Orphan child rows (action plans / attachments whose parent downtime was hard-deleted in Eagle Eye): skip on import; exclude from seed export when regenerating JSON.  
7. FSC Web users (MHE/FA only): copy bcrypt password + site assignments from `tb_sys_mf_user_site` when present; if Eagle Eye has **no site rows**, import the user **without** site assignment (not a failure).

---

## What this pass does / does not do

| Done | Still open |
|------|------------|
| Phase 1 geo masters + SQL seed fixtures | Phase 2 categories (full CRUD) |
| Phase 4 downtime CRUD + import module | Phase 3 inventories |
| Eagle Eye seed export/import (4 tables) | Phase 5 reports |
| Import provisions (zero-date, orphans, attachment placeholders) | Physical attachment file migration |
| FSC Web user import (password + site scope; user-only when no EE sites) | Export-time orphan pruning in `mhe:export-eagle-eye-seed` |

---

## Next step (human)

Continue **Phase 2** (MHE categories) or **Phase 3** (inventories), or run a fresh export from live Eagle Eye when available. Re-import: `php artisan mhe:import-downtimes --source=eagle_eye_json_seed`.
