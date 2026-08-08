# REMINDER: Disable MHE Downtime in fsc_web when mhe-pms goes live

**Status:** Revisit after mhe-pms port is complete.

Related: [fsc-web-mhe-port-plan.md](fsc-web-mhe-port-plan.md)  
**Cursor plan (for when you ask):** `fsc_web_mhe_disable_when_live.plan.md`

> **When ready, say in chat:** `let's do the fsc_web MHE disable plan`

---

## One-line answer

Hide **MHE Downtime transaction** from the fsc_web menu via the **Mods** masterfile UI (`is_visible=0`). Do **not** comment out routes in `web.php`. Do **not** hide MHE Dashboard (summary / utilization / uptime).

---

## MHE Downtime vs MHE Dashboard (do not mix these up)

| What | `tb_sys_mf_mod` | Routes | Disable when mhe-pms is live? |
|------|-----------------|--------|-------------------------------|
| **MHE Downtime** (transactions) | id **46**, code `MHE`, name `MHE Downtime`, url `mhes` | `mhes` CRUD, action plans, attachments | **Yes** — hide mod 46 in Mods UI |
| **MHE Dashboard** (reports) | id **58** `MHE Summary` → `mhes.summary` | `/mhes/summary` | **No** — keep visible |
| | id **101** `MHE Utilization` → `mhes.utilization` | `/mhes/utilization` | **No** — keep visible |
| | id **111** `MHE Uptime` → `mhes.uptime` | `/mhes/uptime` | **No** — keep visible |

Sidebar menu is driven by `sp_call_mod_user_access` — it only shows mods where `is_active=1` **and** `is_visible=1`. Edit flags in **Security → Mods** (`tb_sys_mf_mod`), not in `routes/web.php`.

---

## Other MHE mods to disable (phased, via Mods UI)

| Mod code | Mod id | Disable when |
|----------|--------|--------------|
| `MHE INV` | 125 | Already in mhe-pms |
| `MHE TYPES` | 48 | Already in mhe-pms |
| `MHE CAT` | 45 | After categories ported |
| `MHE 100` | 52 | Anytime (skipped in mhe-pms) |
| `MHE` (downtime only) | 46 | After mhe-pms downtime parity |

**Do not disable:** `DISTRICTS`, `SITES`, `REGIONS`, `SITE TYPES`, or any **Dashboard** mod rows (58, 101, 111).

---

## Cutover steps (when ready)

1. In Mods UI: set `is_visible=0` (and optionally `is_active=0`) on mod **46** (`MHE Downtime`) only
2. Leave dashboard mods **58**, **101**, **111** visible
3. Optionally revoke `tb_sys_mf_mod_access_type` for mod 46 if you want to block access entirely (not just menu hide)
4. For cron/API tied to **downtime posting** only: review `/api/mhes/summary-mail` and MHE email/SMS jobs — dashboard summary mail may still be needed; decide per job
5. Point downtime entry users to mhe-pms

**Do not** comment out routes in `routes/web.php` — that breaks dashboard pages too.

---

## How to pick this back up

Say in chat: **`let's do the fsc_web MHE disable plan`**

Cursor will use plan `fsc_web_mhe_disable_when_live.plan.md` and this doc.
