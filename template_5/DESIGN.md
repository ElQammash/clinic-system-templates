# Template 5 — "Tablet-First Reception" · Design system

Built for the reception desk. The main device is a 10–13" tablet in landscape, and it also works on phones and desktops. Everything is designed for fingers: 48px minimum touch targets, a bottom tab bar, split-screen master/detail pages, a big clock, and high-contrast indigo/purple on near-white.

## Colors

Tokens live in `resources/css/app.css` (`@theme`). You can override the `primary` scale at runtime from **Settings → Branding**.

| Token | Value | Use |
|---|---|---|
| `primary-600` | `#4f46e5` (indigo) | primary buttons, active tab, selected rows |
| `primary-50/100` | `#eef2ff` / `#e0e7ff` | table headers, hover, icon tiles |
| `secondary-600` | `#9333ea` (purple) | the second action tile, gradients |
| `canvas` | `#f4f5fb` | app background |
| `ink` | `#0b0d1f` | text (near-black, high contrast) |
| `muted` | `#4b5168` | secondary text, darker than in other templates so it stays readable at arm's length |
| `line` | `#d9dcea` | 2px borders |
| Status | **solid** high-contrast labels (white text on sky, indigo, violet, emerald, rose; amber with dark text) | badges, timeline blocks |

## Typography

| Language | Font |
|---|---|
| Arabic (default) | **Cairo** (400–800), clear at a distance |
| English | **Lexend** (400–700), designed for readability |

Fonts are self-hosted from Google Fonts at build time (Vite font plugin, `@fonts`). The base size is **16px**: body text 15–18px, page titles `text-3xl/extrabold`, figures up to `text-5xl`.

## Touch rules

- Every interactive element is at least **48px** tall (`.btn` `min-h-12`, inputs `min-h-12`). Primary actions use `.btn-lg` (64px), and the page-header actions grow to 56px.
- Buttons shrink slightly when pressed (`active:scale-[0.97]`) so a tap feels acknowledged.
- `touch-action: manipulation` removes the double-tap zoom delay. Focus rings are thick (3px).
- Checkboxes are 24px and use 2px borders.

## Layout

- **Header** (72px): logo and clinic name, a **big live clock and date** in the centre, a large search field, the language button and a user tile.
- **Bottom tab bar** (always visible, safe-area aware): the four busiest destinations are Home, Waiting room, Appointments and Patients (filtered by permission), plus **More**, which opens a bottom sheet of large tiles for every other section.
- **Modals are side sheets** that slide in from the end edge on tablets, so they're easy to reach with a thumb. On phones they become bottom sheets. Footers have 56px buttons.
- **Login**: an indigo→purple screen with a big white card, 64px fields and a 64px log-in button.

## Key screens

| Screen | Layout |
|---|---|
| Dashboard | Greeting, then **4 big action tiles** (Waiting room, Book appointment, Patients, New invoice), a 4-cell status strip with thick colored edges, today's schedule as 72px rows, and a dark **"Next up" card** (big time, patient, phone, Check-in button). Money and doctor cards sit in the side column. |
| Calendar | A **horizontal timeline**. Each doctor (day view) or day (week view) is a 104px row, and time runs along the reading direction at 6px per minute (15 min = 90px). Appointments are solid, colour-coded blocks, and every free slot is a full-height "+" target. The row labels stay fixed while the timeline swipes sideways, and it scrolls to the red "now" line on load. |
| Patient profile | **Split screen**: on the start side, a summary card (avatar, age, blood type, a red balance tile, allergy box, Call / WhatsApp / Edit) and big vertical tabs; on the end side, the content as 64–80px rows. |
| Waiting room | A **"Call next" strip** with one card per doctor (big queue number in the doctor's colour). Below it, a **split screen**: on the start side, every appointment of the day with lane filters (All, Waiting, Expected, With doctor, Finished); on the end side, the selected patient's details with **64px action buttons** (Check in → Start visit or Call in → Complete → Invoice), plus Patient file and a tap-to-call phone button. The selection survives the 5-second live refresh. |

## Print — `resources/css/print.css` + `resources/views/print`

Large, high-contrast type that is easy for elderly patients to read.

- **Letterhead**: a solid indigo→purple rounded band with white text.
- **Patient box**: a 2px black-bordered box next to a **black file-number tag** with a big number.
- **Prescription**: an indigo "Rx" tile and medicines in bordered cards at 17px bold, with directions at 15px.
- **Invoice**: three info boxes, a black table header with zebra rows, and totals that end in an indigo "Remaining" bar.
- **Receipt**: the amount in a big indigo block.
- **Footer**: a black contact bar, hidden when the clinic has no contact details.

PDF versions (`print/pdf/*`, mPDF) use the indigo palette with larger type (11.5pt base) and a thick indigo rule.
