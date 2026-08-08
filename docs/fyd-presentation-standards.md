# FYD Technologies — Presentation Standards

Authoritative reference for FYD-branded client presentations. Used by `scripts/build_mhe_presentation.js`.

---

## Company

| Field | Value |
|-------|-------|
| **Presenter** | Everett Gaius S. Vergara |
| **Title** | Founder and Chief Technology Officer |
| **Company** | FYD TECHNOLOGIES OPC |
| **Address** | 26F One Vertis Plaza, Vertis North, Agham Road, Brgy. Bagong Pag-asa, Quezon City, Philippines 1105 |
| **Mobile** | +63 917 710 1995 |
| **Direct** | +632 7618 2983 |
| **Email** | hello@fydtech.io |
| **Websites** | www.fydtech.io · www.fydesigns.ph · www.collectimate.io |

---

## Color palette (from fydtech.io, light theme)

Extracted from [fydtech.io](https://www.fydtech.io) on 2026-08-08.

| Token | Hex | HSL (source) | Usage |
|-------|-----|--------------|-------|
| `background` | `#FAFAFA` | hsl(0 0% 98%) | Slide background |
| `surface` | `#FFFFFF` | — | Cards, table rows |
| `primary` | `#5B4FE9` | hsl(243 75% 59%) | Titles, accents, links |
| `primaryLight` | `#EDE9FE` | — | Tinted boxes, table headers |
| `text` | `#1E293B` | — | Body text |
| `textMuted` | `#737373` | hsl(0 0% 45%) | Subtitles, footer |
| `border` | `#E2E8F0` | hsl(220 13% 91%) | Table borders, dividers |
| `success` | `#16A34A` | — | Confirmed / positive status |
| `warning` | `#F59E0B` | — | With Findings / Posted |
| `danger` | `#DC2626` | — | Rejected / Cancelled |

**Client context (FAST Logistics app, not FYD branding):** FAST Blue `#005BAC`

---

## Typography

| Element | Font | Size | Weight |
|---------|------|------|--------|
| Slide title | Calibri (fallback for Space Grotesk) | 28–32pt | Bold |
| Main title (cover) | Calibri | 36pt | Bold |
| Subtitle | Calibri | 20–24pt | Regular |
| Body / bullets | Calibri | 14–16pt | Regular |
| Table text | Calibri | 11–12pt | Regular |
| Footer | Calibri | 9pt | Regular, muted |

Web fonts on fydtech.io: **Space Grotesk**, **Inter**, **Space Mono** — use Calibri in PowerPoint for compatibility.

---

## Layout

- **Aspect ratio:** 16:9 widescreen (`LAYOUT_WIDE`)
- **Title slide:** FYD logo top-left; no footer
- **Content slides:** Left accent bar (primary color); FYD logo top-left (small)
- **Footer (all content slides):** `FYD TECHNOLOGIES OPC | www.fydtech.io | Confidential`
- **Approach:** Talking-point cards — presenter demos live application for visuals

---

## Assets

| File | Source |
|------|--------|
| `docs/presentations/assets/fyd-logo.png` | https://www.fydtech.io/images/logo.png |
| `docs/presentations/assets/fydtech-home.html` | Cached homepage for color extraction |

---

## Regenerate deck

```bash
cd mhe-pms/scripts
node build_mhe_presentation.js
```

Output: `docs/presentations/MHE-System-Overview-FAST.pptx`
