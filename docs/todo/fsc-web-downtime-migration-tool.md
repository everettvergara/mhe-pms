# Migrate MHE Downtime Data

Super-admin page at `/system/downtime-migration-tool`. It reads fsc_web through `FSC_WEB_DB_*` and copies MHE downtimes for one district, then the sites the admin picks.

A row is migrated with its action items and attachment records. Attachment files stay in fsc_web. The fsc_web downtime id is stored on `mhe_downtimes.legacy_eagle_eye_id`, so a second run skips that row, including when the local row was soft-deleted.

## How to run it

1. Sign in as a user with `is_super_admin`.
2. Open **Migrate MHE Downtime Data** under System.
3. Choose a district. Sites load for that district. Leave sites empty to include the whole district.
4. **Count** shows how many rows are ready, already migrated, or blocked.
5. **Preview** lists the rows. Ready rows can be ticked, or use **Migrate all ready rows**.
6. Type `I CONFIRM AND UNDERSTAND`.

One run copies at most `FSC_WEB_DOWNTIME_MAX_ROWS` (default 2000). Narrow the site or date filter to go past that. Each downtime is saved in its own transaction. The batch id is `mhe_downtimes.legacy_import_batch_id`, and the downtime list can be filtered with `filters[legacy_batch]`.

Blocked rows are not copied. Fix the master (or run the User Migration Tool for the creator) and preview again.

## Master matching

Codes are trimmed, case-insensitive, and repeated spaces are collapsed. Soft-deleted masters block the row as inactive / deleted instead of being treated as missing.

| fsc_web | mhe-pms | Match |
|---|---|---|
| `tb_fin_mf_district.code` | `districts.district_code` | code |
| `tb_fin_mf_site.code` | `sites.site_code` | `{code}-ee{id}` first, then the code. Site must belong to the selected district |
| `tb_sf_mf_mhe_type.code` | `mhe_types.code` | code |
| `tb_sf_mf_mhe_category.code` | `mhe_categories.code` | code |
| `tb_sys_mf_user` | `users` | `eagle_eye_import_maps` (`entity_type=user`) first, then `users.username` = fsc user code. Maps that point at `import@system.local` are ignored |
| `ref_unit_no` | `mhe_inventories.unit_no` | same site, case-insensitive. No match is a warning only |

Duplicate fsc site codes `CDI La Union` (id 149) and `JTI WH1` (id 233) resolve to the `-ee{id}` site codes created by the seeder.

## Downtime fields

| fsc_web `tb_sf_tr_mhe` | mhe-pms `mhe_downtimes` |
|---|---|
| `id` | `legacy_eagle_eye_id` |
| `title` | `title` |
| `site_id` | `site_id` |
| `mhe_type_id` | `mhe_type_id` |
| `mhe_category_id` | `mhe_category_id` |
| `ref_unit_no` | `ref_unit_no` (blank becomes `UNKNOWN`) plus `mhe_inventory_id` / `supplier_id` when the unit matches |
| `date_of_incident` | `date_of_incident` (invalid dates fall back to `created_at`; both invalid blocks the row) |
| `uptime` | `uptime` (copied, not recalculated) |
| `hours_down` | `hours_down` (copied) |
| `time_from`, `time_to` | time only |
| `root_cause`, `description` | same |
| `approver_remarks` | `approver_remarks` |
| `w_spare_unit` | `w_spare_unit` (null becomes false) |
| `status_id` | `status`: 1 Draft, 2 Posted, 3 Cancelled, 4 For Approval → Posted, null Draft |
| `created_by_id` | `created_by` and `updated_by` |
| `created_at`, `updated_at` | preserved |
| run id | `legacy_import_batch_id` |

Posted and For Approval set `posted_by` / `posted_at` from the creator and the fsc `updated_at`. Cancelled sets `cancelled_by` / `cancelled_at` the same way.

## Action items

Every action item is stored as **Confirmed**, which is the implemented state in mhe-pms. `date_implemented` uses the fsc date, then the downtime uptime, then the action plan date, then the incident date. `confirmed_by` is the super admin running the tool. `unit_safe_guaranteed` stays false. The creator is the downtime creator, because fsc action plans have no user. `action_plan_no` is `EE-DT-AP-{fsc id}`.

The workflow services are not called, so no notification is sent and uptime is not recalculated from the action items.

## Attachments

`tb_sf_tr_mhe_attachment` and `tb_sf_tr_mhe_action_plan_attachment` become `attachments` rows with `file_path` `eagle-eye/legacy/{filename}`. The single `tb_sf_tr_mhe.attachment` column is included when that filename is not already a child row. The files themselves are not copied, so the downtime page shows them as unavailable until the files are placed in `storage/app/public/eagle-eye/legacy/`.

## Not copied

- `tb_fin_mf_site.site_type_id`
- `tb_sf_tr_mhe_100`
- fsc inventory rows (mhe-pms inventory comes from its own fixture)
- attachment file bytes
