# Template 4 — "Luxury Minimal" · Design system

An editorial, high-end look for aesthetic, dermatology, cosmetic-dental and private-practice clinics. It uses black, white and a single gold accent, generous whitespace, serif headings, hairline borders and square corners, with no shadows and no fills.

## Colors

Tokens live in `resources/css/app.css` (`@theme`). The `primary` scale is **gold**, and you can override it at runtime from **Settings → Branding**. Gold is only an accent: rules, numerals, active markers and links. Large surfaces stay white.

| Token | Value | Use |
|---|---|---|
| `primary-500` | `#b08d57` (gold) | hairline rules, active underline, numerals, chart line |
| `primary-600/700` | `#9a7746` / `#7d5f39` | eyebrow text, hover on black buttons |
| `ink` | `#0a0a0a` | text, primary buttons |
| `canvas` | `#fbfaf8` | app background (warm off-white) |
| `muted` | `#7a7a7a` | secondary text |
| `line` | `#e7e3dc` | 1px hairlines |
| Grays | Tailwind `neutral` | |
| Status | small coloured **dots** only (no tinted fills) | |

## Typography

| Role | Arabic | English |
|---|---|---|
| Headings (`font-serif`, h1–h3) | **Amiri** | **Cormorant Garamond** |
| Body (`font-sans`, light 300) | **Alexandria** | **Jost** |

Fonts are self-hosted from Google Fonts at build time (Vite font plugin, `@fonts`). Headings are large: page titles `text-5xl` and section titles `text-3xl`. The **eyebrow** (`.eyebrow`) is a tiny uppercase gold label with wide tracking that sits above titles. Tracking is reset to 0 in Arabic, because letter-spacing breaks Arabic joining.

## Shape & spacing

- Square corners everywhere (`--radius-card: 0`), 1px hairlines and no shadows. The only exception is the modal's long soft shadow.
- **Inputs are underlined** (bottom border only) and turn black on focus. Textareas keep a full hairline frame.
- Buttons are solid black (`.btn-primary`) or black-outlined (`.btn-secondary`), uppercase with wide tracking in English and normal text in Arabic. Hovering a black button turns it deep gold.
- Spacing is generous: page header `mb-10`, sections `gap-16`, table rows `py-4`.

## Layout

- **Index sidebar** (18rem, start side): a serif monogram in a thin gold frame and the clinic name, then a **text-only** menu with serif numerals (01, 02, …) and group eyebrows. The active item gets a gold dash and black text. On phones it becomes a drawer over a white veil.
- **Top bar**: an underlined search field, the language as an eyebrow link, and initials in a hairline circle.
- **Content** is limited to `max-w-6xl` with wide gutters.
- **Login**: a split editorial page. The black side has the monogram, a large serif "Care, refined." statement and a gold hairline; the white side has the underlined form.

## Components

| Component | Notes |
|---|---|
| `<x-icon>` | Heroicons **outline** at a hairline stroke (`stroke-[1.1]`); `solid` is ignored on purpose. Used sparingly. |
| `<x-page-header>` | The subtitle becomes a gold eyebrow above a big serif title, followed by a gold rule. |
| `<x-badge>` | A coloured dot plus quiet text. |
| `<x-modal>` | A white sheet with a gold top hairline and serif title, over a blurred white veil. |
| `<x-toaster>` | A black bar with a gold dash. |
| `<x-clinic-logo>` | Logo, or a serif monogram in a gold frame. |

## Key screens

| Screen | Layout |
|---|---|
| Dashboard | Date eyebrow, then a huge serif greeting with a gold comma. Four large serif figures separated by hairlines (no cards). A single gold **line** chart with no fill. Doctors as hairline rows with a thin gold progress line. A numbered setup list. Side column: today's appointments with gold serif times. |
| Calendar | A **ledger**: a week strip of serif day numbers (the selected day is underlined in gold), text toggles for day/week and doctors, then a table with one hairline row per hour (serif gold hour on the start side) and one column per doctor or day. Entries are quiet text lines with a status dot, plus a discreet "Available · 16:30" link that books the first free slot in that hour. |
| Patient profile | A **dossier**: file-number eyebrow, huge serif name, allergies as red-dot text, a six-cell hairline fact grid (age, gender, blood type, phone, opened on, balance), text tabs with a gold underline, and serif lists for visits, appointments and the timeline. |
| Waiting room | A **concierge list**: a "now serving" row with giant gold serif queue numbers per doctor and a "Call next →" text link; waiting patients as hairline rows with large serif numbers; a side list of expected arrivals. |

## Charts

Chart.js (`resources/js/app.js`). The revenue line is 1.25px gold with monotone interpolation, no points and no fill. The grid is barely visible and has no axis borders.

## Print — `resources/css/print.css` + `resources/views/print`

- **Letterhead**: centred serif monogram in a gold frame, the clinic name in serif, a gold hairline, then the doctor's name and title in small caps.
- **Patient fields**: a three- or four-cell hairline row with small-caps captions.
- **Prescription**: a large gold italic ℞ and serif medicine names.
- **Invoice**: a big serif invoice number and a "Billed to" block. The items table uses hairline rows; the totals end with a gold rule and a serif "Remaining" figure.
- **Footer**: a centred gold dash and contact details in small caps.

PDF versions (`print/pdf/*`, mPDF) use the same black and gold palette with 1px gold rules. Clinic names use DejaVu Serif in English and DejaVu Sans in Arabic, because DejaVu Serif has no Arabic glyphs.
