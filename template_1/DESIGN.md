# Template 1 — "Clinical Clean" · Design system

Classic medical look: white surfaces, teal/blue accents, right-hand sidebar (in Arabic), card-based pages, rounded corners and soft shadows.

## Colors

Tokens live in `resources/css/app.css` (`@theme`). The `primary` scale can be overridden at runtime from **Settings → Branding**: the server generates shades 50–950 from one color (`App\Support\ColorPalette`) and injects them as CSS variables.

| Token | Value | Use |
|---|---|---|
| `primary-600` | `#0d9488` (teal) | buttons, active nav, links |
| `primary-50/100` | `#f0fdfa` / `#ccfbf1` | active nav background, soft highlights |
| `secondary-600` | `#2563eb` (blue) | gradients, secondary accents |
| `canvas` | `#f6f8fb` | app background |
| `ink` | `#0f172a` | headings, body text |
| `muted` | `#64748b` | secondary text |
| `line` | `#e5eaf0` | borders, dividers |
| Status | emerald / amber / rose / sky / violet (Tailwind) | badges |

## Typography

| Language | Font | Weights |
|---|---|---|
| Arabic (default) | **IBM Plex Sans Arabic** | 400, 500, 600, 700 |
| English | **Inter** | 400–700 (variable) |

Fonts are downloaded from Google Fonts at build time by the Vite font plugin and served locally (`@fonts` in the layouts). `html[lang]` switches which family comes first.

Scale: page title `text-2xl/bold`, card title `text-base/semibold`, body `text-sm`, captions `text-xs`.

## Spacing, radius, shadows

- Page padding: `px-4 sm:px-6 lg:px-8`, `py-6 lg:py-8`; content max width `max-w-7xl`.
- Cards: `.card` → `rounded-2xl` (`--radius-card: 1rem`), 1px `line` border, `--shadow-soft`.
- Inputs & buttons: `rounded-lg`, 40–44px height.
- Popovers & modals: `--shadow-lift`, `rounded-2xl` (bottom sheet on phones).

## Layout

- **Sidebar**: 18rem fixed panel on the *start* side (right in RTL, left in LTR), grouped navigation, user card at the bottom. Off-canvas drawer below `lg`.
- **Top bar**: sticky, translucent white, global patient search, language switch, user menu.
- **Login**: split screen — teal/blue gradient brand panel with a cross and ECG-line illustration + a clean form.

## Components (`resources/views/components`)

| Component | Notes |
|---|---|
| `<x-icon name>` | Heroicons (outline; solid for the active nav). Maps the semantic keys used by `App\Support\Navigation`. |
| `<x-field label for error hint>` | Label + control + error + hint. |
| `<x-modal wire:model title>` | Centered dialog, bottom sheet on mobile, focus trap. |
| `<x-page-header title subtitle>` + `actions` slot | |
| `<x-badge color>` | Pill with soft background + ring. |
| `<x-clinic-logo>` | Uploaded logo, or initials on a teal→blue gradient. |
| `<x-toaster>` | Toasts from Livewire `toast` events / `session('toast')`. |
| CSS classes | `.card`, `.btn` (`-primary`, `-secondary`, `-ghost`, `-danger`, `-sm`), `.form-control`, `.form-check`, `.form-label`, `.table-clean`, `.nav-link`, `.ltr-nums`. |

## RTL rules

- Always use logical utilities (`ms-*`, `pe-*`, `start-*`, `text-start`, `border-e`).
- Phones, e-mails, file numbers and codes: `dir="ltr"` on inputs, `.ltr-nums` on text.
- Directional icons (logout, arrows) get `rtl:-scale-x-100`.

## Key screens

| Screen | Layout |
|---|---|
| Dashboard | Greeting + date, 4 KPI cards with tinted icon tiles, 30-day revenue area chart, today's schedule card with a 4-cell status strip, doctors' revenue bars, outstanding + low-stock cards, setup checklist |
| Calendar | Time grid (1.6 px/min), doctor columns (day) or day columns (week), hatched off-hours, white working windows, hover "+ time" slots, colored left border per visit type, red "now" line; agenda list on phones |
| Patient profile | Gradient banner, overlapping avatar tile, badges (file no., age, gender, blood type, balance), red allergy chips, underline tabs |
| Visit | Main column of cards (vitals → notes → custom fields → ℞ → lab → files → follow-up) + sticky patient summary sidebar |
| Reports | Period chips + date range + doctor filter, KPI grid, bar/doughnut/grouped-bar charts, tables |

## Charts

Chart.js through the Alpine `chart` component (`resources/js/app.js`). Datasets use the placeholders `primary`, `primary-soft`, `secondary`, resolved from the CSS variables at runtime, so a clinic's brand color is applied to charts too.

## Print — `resources/css/print.css` + `resources/views/print`

Classic letterhead (`print/partials/letterhead`): logo + clinic name in teal on one side, doctor name/title/license on the other, then a teal→blue gradient rule. A contact footer with the custom text from Settings.

| Document | Paper | Notes |
|---|---|---|
| Prescription | A5 | Patient strip (name, date, age, weight, file no.) on a mint background, large italic ℞, numbered medicines with dashed separators, follow-up date, signature box |
| Invoice | A4 | Teal header row table, totals block aligned to the end, payments history |
| Receipt | A5 | Centered, amount in a dashed box |
| Lab request | A5 | Numbered test list, signature box |

PDF versions (`print/pdf/*`) are simplified tables with inline CSS for mPDF (DejaVu Sans handles Arabic shaping).
