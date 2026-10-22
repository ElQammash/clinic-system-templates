# Template 3 — "Warm & Friendly" · Design system

A cosy, welcoming look for family, pediatric and dental clinics. It uses a cream canvas with coral and orange accents and warm brown text. Navigation sits in a top bar of pills, the type is large and friendly, emoji act as icons, and almost every shape is round.

## Colors

Tokens live in `resources/css/app.css` (`@theme`). You can override the `primary` scale at runtime from **Settings → Branding**: `App\Support\ColorPalette` generates shades 50–950.

| Token | Value | Use |
|---|---|---|
| `primary-500` | `#f25c49` (coral) | primary buttons, active pill text, accents |
| `primary-50/100` | `#fff5f3` / `#ffe6e1` | soft highlights, chips |
| `secondary-400/500` | `#fb923c` / `#f97316` (orange) | gradients, second chart series |
| `canvas` | `#fdf7ef` | app background (cream) |
| `cream` | `#fbeedd` | nav bar, emoji tiles, table headers, chips |
| `ink` | `#3b2a24` (warm brown) | text |
| `muted` | `#8a7468` | secondary text |
| `line` | `#f0e2d2` | 2px borders, dashed separators |
| Grays | Tailwind `stone` (warm) instead of `slate` | |

## Typography

| Language | Font |
|---|---|
| Arabic (default) | **Baloo Bhaijaan 2** (rounded, 400–800) |
| English | **Nunito** (400–800) |

Fonts are self-hosted from Google Fonts at build time (Vite font plugin, `@fonts`). The base size is **15px**. Page titles are `text-3xl/extrabold` and card titles `text-xl/extrabold`; buttons and labels are bold.

## Shape & depth

- Cards: `rounded-[1.75rem]`, a **2px** cream border and a warm shadow (`--shadow-soft`).
- Buttons are pills (`rounded-full`) with a solid "3D" bottom shadow (`0 3px 0 primary-700`), and they press down 1px when clicked.
- Inputs are `rounded-2xl` with 2px borders and a soft coral focus ring.
- Modals are big `rounded-[2rem]` sheets that pop in with a little bounce (`cubic-bezier(.34,1.56,.64,1)`).
- Tables use rounded header pills and dashed row separators.

## Icons

- **Emoji** (`<x-emoji name>`, `resources/views/components/emoji.blade.php`) appear in navigation, page headers (`<x-page-header emoji="…">`), stat bubbles, tabs, empty states and toasts. They use the same semantic keys as `App\Support\Navigation`. An `.emoji-tile` gives an emoji a cream rounded square.
- **Heroicons solid** (`<x-icon>`) are used for small action icons (edit, plus, close, chevrons) because they're chunkier and friendlier than outline.

## Layout

- **Top bar**: the logo bubble with clinic name and specialty, a centred pill search ("Find a patient…"), a 🌍 language switch and a user chip with a dropdown.
- **Navigation bar**: a cream rounded strip under the top bar. Items from the first menu group are pills; the other groups (🩺 Clinical, 💰 Finance, ⚙️ Administration) open as rounded dropdowns. The active pill is white with coral text.
- **Phones/tablets**: a ☰ button opens a bottom sheet of big emoji tiles, three or four per row.
- **Login**: a centred rounded card on cream with soft coral/orange blobs, floating emoji (🩺 💊 ❤️ 📅), a waving 👋 and a 👀/🙈 show-password toggle.

## Key screens

| Screen | Layout |
|---|---|
| Dashboard | A coral→orange greeting card (☀️/🌇/🌙 by time of day, waving hand, "N more patients expected today", quick buttons). Pastel stat bubbles with emoji tiles. Today's patients as rounded rows with colored initials avatars. Rounded revenue bars. Doctor cards. Low-stock chips. A setup checklist with a gradient progress bar. |
| Calendar | An **agenda board** instead of a time grid: one rounded column per doctor (day view) or per day (week view), with a header tinted in the doctor's color. Appointments are "stickers" (time box + name + status emoji 📌 👍 🪑 🩺 ✅ ❌ 👻). Blocked periods show as 🚫 dashed cards. Runs of free slots appear as green 🌱 "Free times" chips; tap one to book it. |
| Patient profile | A big header card with a gender-tinted avatar and emoji fact chips (🪪 file no., 🎂 age, 🩸 blood type, 📞 phone, 🧾 balance), plus a red ⚠️ alerts strip. Emoji pill tabs. Visits, invoices and appointments appear as cards, not tables, and the timeline is a dotted vertical line of cards. |
| Waiting room | Doctor "room" cards with a big now-serving bubble in the doctor's color and a 🔔 Call next button. Waiting patients are **ticket cards** (notched sides, big number) with a mood emoji for the wait (🙂 under 15 min, 😐 under 30, 😟 after that). Expected-today list, plus a ✅ finished drawer. |

## Charts

Chart.js through the Alpine `chart` component (`resources/js/app.js`). Bars are fully rounded (`borderRadius: 999`). The colors `primary`, `primary-soft` and `secondary` resolve from CSS variables, so branding applies.

## Print — `resources/css/print.css` + `resources/views/print`

- **Letterhead**: a rounded cream band holding the coral initials bubble (or logo), the clinic name in coral and the doctor block, with three coral dots underneath.
- **Patient info** is shown as rounded chips, medicines as rounded bordered cards with numbered bubbles, and the ℞ sits in a coral circle.
- Each document ends with a friendly line: "Get well soon! 💐" on prescriptions and "Thank you for trusting us! 💛" on invoices. The contact footer is a cream pill with 📍 📞 ✉️, and it's hidden when the clinic has no contact details.

| Document | Paper |
|---|---|
| Prescription | A5 |
| Invoice | A4 — rounded header row, cream totals card ending in a coral "Remaining" pill |
| Receipt | A5 — big coral amount bubble, 2-column info cards |
| Lab request | A5 — numbered test cards |

PDF versions (`print/pdf/*`, mPDF) use the same warm palette with rounded boxes. They contain no emoji, because DejaVu Sans has no colour emoji.
