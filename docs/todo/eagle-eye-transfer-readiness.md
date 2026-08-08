# Eagle Eye (`fsc_web`) → mhe-pms transfer notes

Status as of 2026-08-07. **Phase 1 geo masters done** in `mhe-pms`: Region + District + Site CRUD and seeders from the SQL dump.

## Workspace map

```text
d:\Everett\Codes\mhe\
├── fsc_web\                              ← Eagle Eye Dashboard (source program)
├── eagleeyefastlogi_fsc_dashboard.sql    ← DB dump (data source for geo fixtures)
├── mhe-pms\                              ← TARGET
└── mhe-pms-temp\                         ← ignore
```

| Folder | Role |
|--------|------|
| `fsc_web` | Eagle Eye Dashboard — Laravel 9 Blade app; finance masters for districts/sites/regions |
| `mhe-pms` | MHE PMS — Laravel ^13.8; Region / District / Site masters |
| `mhe-pms-temp` | Stock Laravel 13 scaffold — do not transfer into |

Target for all future transfers: **`mhe-pms` only**.

## Hierarchy

```text
tb_fin_mf_district  1──*  tb_fin_mf_site
tb_fin_mf_region    1──*  tb_fin_mf_site   (optional region_id)
                         └── site_type_id (nullable; still out of scope)
```

Region is **not** a parent of District. Both attach to Site. `tb_sf_mf_location` is an AIR warehouse zone — not geographic.

## Field mapping → mhe-pms

| Eagle Eye | mhe-pms |
|-----------|---------|
| `tb_fin_mf_region.code/name/remarks/is_active` | `regions.region_code/region_name/description/status` |
| `tb_fin_mf_district.code/name/remarks/is_active` | `districts.district_code/district_name/description/status` |
| `tb_fin_mf_site.code/name/remarks/is_active` | `sites.site_code/site_name/description/status` |
| `site.district_id` | resolve by **district code** |
| `site.region_id` | resolve by **region code** (nullable) |
| `tb_sf_mf_mhe_type.code/name/is_active` | `mhe_types.code/description/status` |

`is_active` → `Active` / `Inactive`.

## Seeded from dump (not fsc_web seeders)

Fixtures: `mhe-pms/database/data/{regions,districts,sites,mhe_types}.json`  
Seeders: `RegionSeeder` → `DistrictSeeder` → `SiteSeeder` → `MheTypeSeeder`

| Master | Count |
|--------|-------|
| Regions | 3 (Luzon, Vizayas, Mindanao) |
| Districts | 20 |
| Sites | 240 (231 Active / 9 Inactive; 32 with region) |
| MHE Types | 5 (CB, RT, LT, ST, JL) — overrides prior FL/RT/PT/ES; leftovers soft-deleted |

### MHE Types mapping

| Eagle Eye `tb_sf_mf_mhe_type` | mhe-pms `mhe_types` |
|-------------------------------|---------------------|
| `code` | `code` |
| `name` | `description` |
| `is_active` | `status` (`Active` / `Inactive`) |

### Collision rule

Dump had duplicate `code`/`name` for `CDI La Union` and `JTI WH1`. Keep first occurrence; suffix later ones as `{code}-ee{id}` / `{name} (ee{id})`.

### Still deferred

- Site Type master / `site_type_id`
- MHE categories (`tb_sf_mf_mhe_category`)
- Eagle Eye UI / Select2 / `set_sites()` ACL port
- Editing `fsc_web`

## Related

- Port plan: [`fsc-web-mhe-port-plan.md`](fsc-web-mhe-port-plan.md)
- Admin inventory: [`fsc-web-mhe-admin-inventory.md`](fsc-web-mhe-admin-inventory.md)
