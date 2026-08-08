# FSC Web user import

Status: **implemented** (2026-08-07). Live MySQL only — not part of JSON/SQL-dump downtime seed path.

See also: [fsc-web-mhe-port-plan.md](fsc-web-mhe-port-plan.md) Phase 4c.

---

## Commands

```bash
# Preview counts (no writes) — use FscUserImportService::preview() from tinker or a one-off script
php artisan fsc:import --source=eagle_eye_mysql --users --confirm --dry-run

# Live import (destructive purge + users)
php artisan fsc:import --source=eagle_eye_mysql --users --downtimes --confirm
```

Admin UI: `/mhe-downtimes/import/run` — checkbox **Users (MHE access, live MySQL only)**.

Requires `FSC_WEB_DB_*` env vars or `--host` / `--database` / `--username` / `--password`.

---

## Who gets imported

Active Eagle Eye users with **MHE module** access (via `tb_sys_mf_mod_access_type` + mod code `MHE`) **or** **FA** (Full Access).

**Not imported:** supplier users, inactive users, users without MHE/FA access.

Implementation: `app/Services/FscWebImport/FscUserImportService.php` → `fetchMheUsers()`.

---

## What is copied

| Eagle Eye | mhe-pms |
|-----------|---------|
| `tb_sys_mf_user.id` | `eagle_eye_import_maps` (`entity_type=user`, `legacy_id`) |
| `tb_sys_mf_user.code` | `users.username` |
| `tb_sys_mf_user.name` | `users.name` |
| `tb_sys_mf_user.email` | `users.email` |
| `tb_sys_mf_user.password` | `users.password` (raw bcrypt copy) |
| `tb_sys_mf_user.mobile_no` | `users.contact_number` |
| `tb_sys_mf_user.is_active` | `users.status` |
| FA access type | `users.is_super_admin = true` |
| (all imported) | `users.role_id` → FAST Administrator |
| `tb_sys_mf_user_site` + `tb_fin_mf_site.code` | `supplier_sites` (site scope; district via `sites.district_id`) |

**Not copied:** profile photos, per-module EE permissions, supplier links.

---

## Site assignment rules

1. Load all rows from `tb_sys_mf_user_site` joined to `tb_fin_mf_site` for each imported user.
2. Match `tb_fin_mf_site.code` → mhe-pms `sites.site_code`.
3. `sync` matched site ids onto `supplier_sites` (clears any prior supplier assignment).

### No site rows in Eagle Eye

If a user qualifies for import (MHE/FA) but has **zero** `tb_sys_mf_user_site` rows:

- **Import the user** (password, role, map).
- **Do not** assign any sites.
- **Not a failure** — count appears as `users_without_sites` in import metrics only.

**Known example (2026-08-07 preview):**

| EE id | Username | Name | Notes |
|------:|----------|------|-------|
| 559 | `amollosa` | Albert M. Ollosa | MHE Transaction + PCE + Safety Transaction; no `tb_sys_mf_user_site` rows in Eagle Eye |

Do not fabricate sites or skip the user unless product decides otherwise later.

### Unmapped site codes

If Eagle Eye assigns a site code that does not exist in mhe-pms `sites`, that assignment is skipped (`sites_unmapped` count). Other valid sites for the same user are still applied.

---

## Passwords

- Bcrypt hash is copied **byte-for-byte** from Eagle Eye (salt is inside the hash string).
- Import uses `DB::table('users')->update(['password' => ...])` to avoid Laravel's `hashed` cast double-hashing.
- Password changes in mhe-pms after import replace the stored hash; there is no sync back to fsc_web.
- Re-running `fsc:import --users` overwrites passwords from Eagle Eye again.

---

## Skip / failure cases

| Case | Behavior |
|------|----------|
| Email used by another username in mhe-pms | User skipped (`users_skipped`) |
| No MHE/FA access | Not in import set |
| No EE site rows | User imported, no sites (`users_without_sites`) |
| EE site code missing in mhe-pms | That site skipped; user still imported |

---

## Preview metrics (local DB, 2026-08-07)

| Metric | Value |
|--------|------:|
| `users_total` | 469 |
| `users_would_import` | 469 |
| `users_skipped_email_conflict` | 0 |
| `site_assignments` | 9,977 |
| `sites_unmapped` | 0 |
| `users_without_sites` | 1 |
