# Template 2 — "Dark Pro" · Design system

A modern, tech-forward look for busy clinics and power users. The theme is dark by default, with a light theme one click away. It uses navy/graphite surfaces with an emerald accent, a collapsible icon-rail sidebar, dense tables, hairline borders, sharp corners, mono figures and a Ctrl/⌘ + K command palette.

## Colors

Tokens live in `resources/css/app.css`. `@theme` holds the light values, and the `.dark` block on `<html>` overrides the neutrals. You can override the `primary` scale at runtime from **Settings → Branding**: `App\Support\ColorPalette` generates shades 50–950 and they are injected as CSS variables.

| Token | Dark | Light | Use |
|---|---|---|---|
| `primary-500` | `#10b981` (emerald) | same | primary buttons (dark text `slate-950`), active markers |
| `primary-400` | `#34d399` | same | links, active nav text, numbers on dark |
| `secondary-500` | `#06b6d4` (cyan) | same | secondary series |
| `canvas` | `#0b1220` | `#f3f5f8` | app background |
| `surface` | `#121a29` | `#ffffff` | cards, panels |
| `subtle` / `raised` | `#182233` / `#1b2537` | `#f1f4f8` / `#ffffff` | table headers, hover, popovers |
| `ink` / `ink-soft` | `#e7edf6` / `#b4c0d3` | `#0b1220` / `#334155` | text |
| `muted` | `#7d8ba1` | `#64748b` | secondary text, labels |
| `line` / `line-strong` | `#233047` / `#33425c` | `#e2e7ee` / `#cbd3de` | 1px borders |

In dark mode, the Tailwind tint shades (`*-50…200`) of primary, emerald, rose, amber, sky, blue, violet, orange and pink become translucent overlays, and `*-700…900` become light text shades. Views written with "light" utilities (`bg-amber-50 text-amber-700`) therefore stay readable in both themes.

The theme is stored in `localStorage.theme` and applied before first paint by an inline script, so there is no flash. Charts redraw when the theme changes.

## Typography

| Role | Font |
|---|---|
| Arabic (default) | **Readex Pro** |
| English | **Manrope** |
| Figures, codes, file numbers, money | **JetBrains Mono** (`font-mono`, `.num`, `.ltr-nums`) |

Fonts are self-hosted from Google Fonts at build time by the Vite font plugin (`@fonts`). The base size is 13.5px. Page titles are `text-lg/semibold`, section titles `text-[13px]/semibold`, and labels are 10–11px uppercase with tracking.

**Arabic rule:** letter-spacing breaks Arabic letter joining. An unlayered CSS rule resets every `tracking-*`, `.form-label` and table header to `letter-spacing: 0` under `[dir=rtl]`, and the mono stack falls back to Readex Pro for Arabic glyphs.

## Shape & density

- Radius: cards 4px (`--radius-card`), buttons/inputs `rounded` / `rounded-sm`, badges 3px. No soft shadows; elevation comes from borders. Only popovers get `--shadow-lift`.
- Tables (`.table-clean`) are dense: 32–36px rows, sticky uppercase headers on `subtle`, and tabular mono numbers.
- KPI tiles sit in a `gap-px` grid on a `line` background, which produces hairline dividers without double borders.
- Page width goes up to `max-w-[1600px]` and gutters are smaller than in template 1.

## Layout

- **Icon rail**: a 56px sidebar on the *start* side shows icons only. It expands to 240px on hover (peek), or stays open when pinned. The pin state is stored in `localStorage`, and the rail becomes a drawer on phones. The active item gets an emerald edge marker.
- **Top bar** (48px): breadcrumb (clinic / page), a search button with a `Ctrl K` hint, a theme toggle (sun/moon), a language code button and the user menu.
- **Command palette** (`App\Livewire\Ui\CommandPalette`, template-specific): Ctrl/⌘ + K opens it. It searches patients by name, phone or file number (Arabic-normalized) and jumps to any page you are allowed to see. Use ↑/↓ to move and Enter to open.
- **Login**: a centered card on a dotted grid with a soft emerald glow, plus a "Secure sign-in" eyebrow and icon-prefixed inputs.

## Components (`resources/views/components`)

| Component | Notes |
|---|---|
| `<x-icon name>` | Lucide icons (`mallardduck/blade-lucide-icons`), stroke 1.75 (2.25 when `solid`). Maps the semantic keys used by `App\Support\Navigation`. |
| `<x-modal>` | Top-aligned sharp panel, `Esc` key hint, bottom sheet on phones. |
| `<x-page-header>` | Compact title + subtitle, actions on the end side, hairline underneath. |
| `<x-badge color>` | Small dot + tinted label, 3px radius. |
| `<x-clinic-logo>` | Uploaded logo, or mono initials in an emerald outlined square. |
| `<x-toaster>` | Bottom-start strips with a colored edge. |
| CSS classes | `.card`, `.btn` (`-primary`, `-secondary`, `-ghost`, `-danger`, `-sm`), `.form-control`, `.form-check`, `.form-label`, `.table-clean`, `.nav-link`, `.kbd`, `.num`, `.ltr-nums`. |

## Key screens

| Screen | Layout |
|---|---|
| Dashboard | Date eyebrow + greeting + a "Live" clock. Six KPI tiles in a hairline grid with inline SVG sparklines. 30-day revenue bars. Doctors table with inline bars. Setup checklist. Right column: today's schedule (4-cell status strip + dense list) and low stock. |
| Calendar | Toolbar as one segmented strip. Resource grid at 1.2 px/min: doctors as columns (day), days as columns (week), with an appointment count chip in each header. Off-hours drawn as hairline hatching. Flat chips tinted with the visit-type color (`color-mix`), each with a status dot and queue number. Dashed "+ time" ghost slots and an emerald "now" line. Status legend underneath. |
| Patient profile | Two columns. A sticky meta panel holds the initials tile, an age/gender/blood/balance grid, a red alerts card and contact rows. The main panel has segmented tabs, each a dense table (timeline, visits with Rx count, billing, appointments). |
| Waiting room | A "Now serving" strip (one cell per doctor with the big mono queue number in the doctor's color, plus Call next). Below it, three lanes — Expected → Waiting → With doctor — each with a colored top edge and a count. Waiting times over 30 min turn red. Finished patients are listed under the last lane. |
| Lists | Dense tables, mono numbers, row hover on `subtle`. |

## Charts

Chart.js through the Alpine `chart` component (`resources/js/app.js`). Colors resolve from CSS variables (`primary`, `primary-soft`, `secondary`); grid lines and ticks follow `line` / `muted`, and the chart redraws on `theme-changed`.

## Print — `resources/css/print.css` + `resources/views/print`

Monochrome modern grid that prints well on any laser printer:

- **Letterhead**: a solid black band with a thin accent edge in the clinic color. The clinic name and tagline sit on the start side and the doctor block on the end side, behind a vertical hairline.
- **Data grid**: 1px black-ruled cells with tiny uppercase captions (`.cap`). The **file-number block** is a black tile with the number in large mono type.
- **Tables**: a black header row, 01/02… mono row numbers, and a totals box ending in a black "Remaining" row.
- **Footer**: a 2px black rule with the address and mono contact details.

| Document | Paper | Notes |
|---|---|---|
| Prescription | A5 | Patient grid + file block, diagnosis line, "Rx" header with item count, numbered medicines, follow-up tag, signature line |
| Invoice | A4 | Big mono invoice number, 4-cell info grid, compact items table, payments log (mono) + totals box |
| Receipt | A5 | Amount cell + black payment-method cell, 2×2 info grid |
| Lab request | A5 | Patient grid + file block, checkbox list of numbered tests |

The PDF versions (`print/pdf/*`, mPDF) reproduce the same black band, ruled grid and mono figures with tables and inline CSS (`print/pdf/partials/header`).
