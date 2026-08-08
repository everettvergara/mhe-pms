# fsc_web MHE admin modules — inventory

Status: **inventory only** (2026-08-07). No coding in mhe-pms yet.

- Source (read-only): `d:\Everett\Codes\mhe\fsc_web` (Eagle Eye Dashboard)
- Working project (future write target): `d:\Everett\Codes\mhe\mhe-pms`
- Scope: **admin** MHE modules (not supplier modules)

Edit URLs use plural **`/mhes/...`**, not `/mhe/...`.

---

## Module map

| Area | Module | Routes | Permission | Port intent |
|------|--------|--------|------------|-------------|
| Transaction | MHE Downtime | `/mhes`, `/mhes/create`, `/mhes/{mhe}`, `/mhes/{mhe}/edit` | `MHE` | Port later |
| Transaction | Action plans | `/mhes/action-plan/*` | `MHE` | Port later |
| Transaction | Attachments | DELETE `/mhes/attachment/{id}`, DELETE `/mhes/action-plan/attachment/{id}` | `MHE` | Port later |
| Masters | Categories | `/mhe-categories` | `MHE CAT` | Port later |
| Masters | Inventories | `/mhe-invs` (+ uploader/import) | `MHE INV` | Port later |
| Masters | Types | `/mhe-types` | `MHE TYPES` | Align with existing mhe-pms |
| Masters | MHE 100% uptime | `/mhe-100` | `MHE 100` | **Eliminate — do not port** |
| Masters | Districts | `/districts` | `DISTRICTS` | Align with existing mhe-pms |
| Masters | Regions | `/regions` | `REGIONS` | Decide later |
| Masters | Sites | `/sites`, `/sites/export` | `SITES` | Align with existing mhe-pms |
| Masters | Site types | `/site-types` | `SITE TYPES` | Decide later |
| Reports | Dashboard | `/mhes/summary` | `MHE` | Port later |
| Reports | Utilization | `/mhes/utilization` | `MHE` | Port later |
| Reports | Uptime | `/mhes/uptime` | `MHE` | Port later |

---

## 1. MHE Downtime (transaction)

### Routes (`fsc_web/routes/web.php`)

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/mhes` | `mhes.index` | `tb_sf_tr_mhe_controller@index` |
| GET | `/mhes/create` | `mhes.create` | `@create` |
| POST | `/mhes` | `mhes.store` | `@store` |
| GET | `/mhes/{mhe}` | `mhes.show` | `@show` |
| GET | `/mhes/{mhe}/edit` | `mhes.edit` | `@edit` |
| PUT/PATCH | `/mhes/{mhe}` | `mhes.update` | `@update` |
| DELETE | `/mhes/{mhe}` | `mhes.destroy` | `@destroy` |

Middleware: `auth` + `can:has_access,'MHE'`.

### Submit / post / cancel

Not separate routes. Form sends a status action; `set_status_id()` in `app/Helpers/helpers.php` maps:

| Action | `status_id` |
|--------|-------------|
| save | 1 |
| post | 2 |
| cancel | 3 |

On **post**, dispatches `MheEmailJob` + `MheSmsJob`. List export: `?export=1` → `MheExport`.

### Controller / views / extras

- Controller: `app/Http/Controllers/tb_sf_tr_mhe_controller.php`
- Request: `app/Http/Requests/tb_sf_tr_mhe_request.php`
- Views: `resources/views/tb_sf_tr_mhe/{index,create,edit,show}.blade.php`
- Helpers: `app/Helpers/MheHelpers.php`
- Jobs: `MheEmailJob`, `MheSmsJob`
- Export: `app/Exports/MheExport.php`
- Autocomplete: `GET search/mhe-inv` → inventory unit numbers

### Table: `tb_sf_tr_mhe`

Model: `app/Models/tb_sf_tr_mhe.php`

| Column | Notes |
|--------|--------|
| id | PK |
| title | required, max 255 |
| site_id | FK → `tb_fin_mf_site` |
| hours_down | decimal(18,2) |
| mhe_category_id | FK → `tb_sf_mf_mhe_category` |
| mhe_type_id | FK → `tb_sf_mf_mhe_type` (alter migration) |
| date_of_incident | datetime |
| description | nullable, max 4000 |
| created_by_id | FK → user |
| status_id | FK → status (save/post/cancel) |
| w_spare_unit | alter migration |
| uptime | datetime, nullable |
| ref_unit_no | required in request |
| root_cause | live/model; max 4000 — thin migration coverage |
| time_from, time_to | live/model — thin migration coverage |
| attachment | legacy fillable; files also in attachment table |
| timestamps | |

Migrations (partial): `2023_07_19_025609_create_tb_sf_tr_mhes_table.php`, `2023_07_24_024012_alter_table_tb_sf_tr_mhe_add_mhe_type_id.php`, `2023_09_20_020747_alter_tb_sf_tr_mhe_tb_sf_tr_mhe_action_plan.php`. Prefer restored DB / SQL dump for full live schema.

### Attachments

| Method | URI | Name |
|--------|-----|------|
| DELETE | `/mhes/attachment/{mhe_attachment}` | `mhes-attachment.destroy` |

- Table: `tb_sf_tr_mhe_attachment` — `mhe_id`, `attachment`
- Model: `tb_sf_tr_mhe_attachment`
- Upload path: `storage/attachments/mhe/` (upload on parent store/update)
- Migration: `2023_09_18_075729_create_tb_sf_tr_mhe_attachments_table.php`

### Action plans (“action items”)

| Method | URI | Name |
|--------|-----|------|
| GET | `/mhes/action-plan/create/{mhe}` | `mhes-action-plan.create` |
| POST | `/mhes/action-plan/store` | `mhes-action-plan.store` |
| GET | `/mhes/action-plan/{mhe_action_plan}` | `mhes-action-plan.show` |
| GET | `/mhes/action-plan/{mhe_action_plan}/edit` | `mhes-action-plan.edit` |
| PUT | `/mhes/action-plan/{mhe_action_plan}` | `mhes-action-plan.update` |
| DELETE | `/mhes/action-plan/{mhe_action_plan}` | `mhes-action-plan.destroy` |
| DELETE | `/mhes/action-plan/attachment/{id}` | `mhes-action-plan-attachment.destroy` |

Table **`tb_sf_tr_mhe_action_plan`**: `mhe_id`, `action_plan`, `action_plan_date`, `responsible`, `action_plan_id` → `tb_sf_mf_action_plan`, `date_implemented`, timestamps.

Table **`tb_sf_tr_mhe_action_plan_attachment`**: `mhe_action_plan_id`, `attachment` (live; thin migration). Path: `storage/attachments/mhe_action_plan/`.

Update statuses include `change` / `implement` in action-plan controller.

---

## 2. MHE Master Files

### MHE Categories — `/mhe-categories`

- Resource CRUD → `tb_sf_mf_mhe_category_controller`
- Permission: `MHE CAT`
- Table `tb_sf_mf_mhe_category`: id, code(30), name, remarks, is_active, timestamps
- Migration: `2023_07_19_013040_create_tb_sf_mf_mhe_categories_table.php`
- Views: `resources/views/tb_sf_mf_mhe_category/`
- **mhe-pms:** not present yet

### MHE Inventories — `/mhe-invs`

| Method | URI | Name |
|--------|-----|------|
| GET | `/mhe-invs` | `mhe-invs.index` |
| GET | `/mhe-invs/create` | `.create` |
| GET | `/mhe-invs/uploader` | `.uploader` |
| POST | `/mhe-invs/store` | `.store` |
| POST | `/mhe-invs/uploader/import` | `.import` |
| GET | `/mhe-invs/{mhe_inv}` | `.show` |
| GET | `/mhe-invs/{mhe_inv}/edit` | `.edit` |
| PUT | `/mhe-invs/{mhe_inv}` | `.update` |
| DELETE | `/mhe-invs/{mhe_inv}` | `.destroy` |

- Permission: `MHE INV`
- Controller: `tb_sf_mf_mhe_inv_controller` (+ Excel export/import)
- Table `tb_sf_mf_mhe_inv` (no create-migration in repo; live): district, site, site_id, provider, brand, model, equipment_type, mhe_type_id, unit_no, unit_role, equipment_status, timestamps
- Extras: `MheInvExport`, `MheInvImport`
- **mhe-pms:** not present (aligns with Charles todo: MHE Units)

### MHE Types — `/mhe-types`

- Resource CRUD → `tb_sf_mf_mhe_type_controller`
- Permission: `MHE TYPES`
- Table `tb_sf_mf_mhe_type`: code, name, remarks, is_active
- Migration: `2023_07_24_021539_create_tb_sf_mf_mhe_types_table.php`
- Seeder: `MheTypeSeeder.php`
- **mhe-pms:** already has `/mhe-types` + `MheType` — align, do not duplicate

### MHE 100% uptime — `/mhe-100` (eliminate)

- Resource CRUD → `tb_sf_tr_mhe_100_controller`
- Permission: `MHE 100`
- Table `tb_sf_tr_mhe_100`: yyyymm, site_id, mhe_type_id, trans_ct, (+ live `ref_unit_no`)
- API: `GET /api/mhe-100/insert-monthly` → stored proc
- **Port intent: eliminate — document only, do not bring into mhe-pms**

### Districts — `/districts`

- Resource → `tb_fin_mf_district_controller`
- Table `tb_fin_mf_district`: code, name, remarks, is_active
- **mhe-pms:** already has `/districts` — data/align only (see [eagle-eye-transfer-readiness.md](eagle-eye-transfer-readiness.md))

### Regions — `/regions`

- Resource → `tb_fin_mf_region_controller`
- Table `tb_fin_mf_region`: code, name, remarks, is_active (no migration in repo; live DB)
- **mhe-pms:** not present — decide if needed

### Sites — `/sites`

- Resource + `GET /sites/export`
- Table `tb_fin_mf_site`: code, name, district_id, site_type_id, region_id (live), remarks, is_active
- **mhe-pms:** already has `/sites` (district_id; no region/site_type yet)

### Site Types — `/site-types`

- Resource → `tb_fin_mf_site_type_controller`
- Table `tb_fin_mf_site_type`: code, name, remarks, is_active
- **mhe-pms:** not present — decide if needed

---

## 3. Reports / Dashboard

All on `tb_sf_tr_mhe_controller`, permission `MHE`:

| Route | Name | Purpose |
|-------|------|---------|
| GET `/mhes/summary` | `mhes.summary` | Dashboard charts (`MheHelpers` + `vw_sf_tr_mhe`) |
| POST `/mhes/summary/action-plans` | `mhes.action-plans` | Action-plan panel partial |
| GET `/mhes/utilization` | `mhes.utilization` | Site/day utilization |
| GET `/mhes/uptime` | `mhes.uptime` | Unit uptime grid |
| GET `/api/mhes/summary-mail` | `mhes.summary-mail` | Summary email cron-style |

Views: `resources/views/tb_sf_tr_mhe/{summary,utilization,uptime}.blade.php` (+ `summary/action-plans`).

Related DB views (migrations under `database/migrations/`): `vw_sf_tr_mhe`, `vw_pending_tb_sf_tr_mhe`, `vw_sf_tr_mhe_action_plans`, `vw_mhe_executive`, `vw_sf_tr_mhe_downtime`, `vw_sf_tr_mhe_100`, function `fn_get_action_plan_mhe`.

---

## Overlap with mhe-pms (today)

| Eagle Eye | mhe-pms today |
|-----------|---------------|
| `/districts` | Exists |
| `/sites` | Exists |
| `/mhe-types` | Exists |
| `/mhe-categories`, `/mhe-invs`, downtime `/mhes`, reports | **Missing** |
| `/regions`, `/site-types` | Missing |
| `/mhe-100` | Skip |
| Eagle Eye downtime incidents | Different domain from mhe-pms **Preventive Maintenance** workflow |

---

## Deferred port order (later — not this pass)

1. Geo masters: districts/sites data; decide region + site_type
2. MHE categories + types alignment
3. MHE inventories (units)
4. Downtime transaction + attachments + action plans
5. Summary / utilization / uptime reports
6. Skip mhe-100

No app code, migrations, or seeders until explicitly requested.

---

## Port plan

Phased implementation plan (still docs-only until you say otherwise): [fsc-web-mhe-port-plan.md](fsc-web-mhe-port-plan.md).
