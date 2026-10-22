# Clinic Management System — Master Plan (Phase 1)

Five independent Laravel projects (`template_1` … `template_5`) with an identical backend and a different presentation layer each.

---

## 1. Key decisions (defaults — change any before approval)

| Topic | Decision |
|---|---|
| Laravel / Livewire | Latest stable versions resolved by `composer create-project` at Phase 2 (pinned in each `composer.lock`). |
| Tenancy | **One install per clinic** (single-tenant). No multi-tenant complexity; a clinic = one database. |
| Auth | Custom Livewire login, logout, forgot/reset password. No public registration — users are created by Admin. |
| Money | Stored as **integer minor units** (piastres) in `bigint` columns, with a `Money` cast + formatter. No float rounding bugs. Currency code/symbol from settings (EGP default). |
| Dates | `Africa/Cairo`, Arabic dates via Carbon `ar` locale (e.g. «الثلاثاء ٢٠ أكتوبر ٢٠٢٦»). Digit style (Western `123` vs Arabic-Indic `١٢٣`) is a setting; default **Western** digits (clearer for phones/amounts). |
| Phone | Egyptian mobile validation `^01[0125][0-9]{8}$` via a reusable `EgyptianPhone` rule; stored normalized; `+20` added only for wa.me links. Rule can be relaxed in settings for other countries. |
| Settings | `settings` table (key → JSON value), read through a `Settings` service cached forever and flushed on save. |
| Primary color override | Each template defines its palette as CSS variables (Tailwind v4 `@theme`). The settings color overrides `--color-primary-*` at runtime (shades generated server-side), so rebranding needs no rebuild. |
| Custom fields | `custom_fields` definitions table + a JSON `custom_data` column on `visits` (and `patients`). Fields can be tied to a specialty; validation rules are generated from the definitions. |
| Live queue | `wire:poll` (5 s) — works on any shared host. Laravel Reverb can be added later without changing the services. |
| Calendar | **Custom Livewire day/week grid** (no FullCalendar) so each template can restyle it completely. |
| Printing | Browser print with dedicated print layouts (best Arabic shaping) as the primary path; **mPDF** for downloadable PDFs (dompdf cannot shape Arabic correctly). |
| Excel export | `spatie/simple-excel` (OpenSpout) — lightweight streamed XLSX. |
| Attachments | Private `local` disk (`storage/app/private/patients/{id}`), served only through an authorized controller route. No public URLs for medical files. |
| Double booking | Checked inside a DB transaction with `lockForUpdate()` on the doctor's overlapping appointments (MySQL has no exclusion constraints). Blocked periods + working hours are validated too. |
| Doctor visibility | Default: a doctor sees patients they have an appointment/visit with and edits only their own visits. Setting `doctors_share_records` (default **on** for single-doctor clinics) lets doctors read all medical histories. |
| Tests | Pest on **SQLite in-memory** (fast, portable for buyers); the suite is also run once against MySQL in Phase 7 to verify FK/index behaviour. |
| Code sharing across templates | Backend is copied byte-identical. A root script `tools/sync-backend.ps1` (+ `.sh`) copies backend directories from `template_1` into the other four, so future bug fixes are applied once and propagated. |
| Dev database | Local MySQL on 127.0.0.1:3306 (Herd), one DB per template: `clinic_t1` … `clinic_t5`. |

---

## 2. Packages

**Composer**
- `laravel/framework`, `livewire/livewire`
- `spatie/laravel-permission` — roles/permissions
- `spatie/laravel-activitylog` — audit trail (views/edits of medical records, invoice deletes)
- `spatie/laravel-backup` — daily DB backup (scheduled)
- `carlos-meneses/laravel-mpdf` — Arabic-capable PDF
- `spatie/simple-excel` — XLSX/CSV exports
- `blade-ui-kit/blade-icons` + one icon set per template (see §6)
- dev: `pestphp/pest`, `pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-livewire`, `laravel/pint`, `fakerphp/faker` (ar_EG locale)

**npm**
- `tailwindcss` v4 + `@tailwindcss/vite`, `@tailwindcss/forms`
- `chart.js` (dashboard/report charts)
- Alpine.js comes bundled with Livewire (plus `@alpinejs/focus`, `@alpinejs/collapse` plugins)

---

## 3. Database schema

`(FK)` = foreign key with index; `SD` = soft deletes. All tables have timestamps.

### Identity & config
| Table | Columns |
|---|---|
| **users** | id, name, email (unique), phone, password, locale (`ar`/`en`), is_active, last_login_at, remember_token |
| **doctor_profiles** | id, user_id (FK unique), title_ar, title_en, specialty, color, slot_minutes, consultation_fee, signature_path, license_number |
| roles / permissions / model_has_* | Spatie Permission tables |
| **settings** | id, key (unique), value (json), group |
| **sequences** | id, key (unique: `patient_file`, `invoice:2026`, `receipt:2026`), prefix, next_value — atomic numbering under row lock |
| **working_hours** | id, doctor_id (FK users, nullable = clinic default), day_of_week (0–6), starts_at, ends_at, is_off · unique(doctor_id, day_of_week, starts_at) |
| **blocked_periods** | id, doctor_id (nullable = whole clinic), starts_at, ends_at, reason · idx(doctor_id, starts_at) |
| **custom_fields** | id, entity (`visit`/`patient`), key, label_ar, label_en, type (text, textarea, number, select, multiselect, checkbox, date), options (json), is_required, specialty (nullable), sort, is_active · unique(entity, key) |

### Patients
| Table | Columns |
|---|---|
| **patients** (SD) | id, file_number (unique), name, phone (idx), phone_alt, gender, birth_date, national_id (unique nullable), address, blood_type, allergies, chronic_diseases, notes, custom_data (json), created_by (FK) · idx(name) |
| **patient_attachments** | id, patient_id (FK), visit_id (FK nullable), lab_request_id (FK nullable), category (xray, lab, image, report, other), title, disk, path, original_name, mime, size, uploaded_by (FK) |

### Scheduling
| Table | Columns |
|---|---|
| **visit_types** | id, name_ar, name_en, service_id (FK nullable → default billed service), default_duration, color, is_active, sort |
| **appointments** | id, patient_id (FK), doctor_id (FK users), visit_type_id (FK), starts_at, ends_at, status, source (booked, walk_in, follow_up, phone), queue_number, checked_in_at, called_at, started_at, completed_at, cancelled_at, cancel_reason, reminder_sent_at, notes, created_by · idx(doctor_id, starts_at), idx(status, starts_at), idx(patient_id) |

### Medical
| Table | Columns |
|---|---|
| **visits** (SD) | id, patient_id (FK), doctor_id (FK), appointment_id (FK unique nullable), visited_at, chief_complaint, examination, diagnosis, notes, bp_systolic, bp_diastolic, pulse, temperature, weight, height, bmi (computed on save), custom_data (json), follow_up_date, follow_up_appointment_id (FK nullable) · idx(patient_id, visited_at), idx(doctor_id, visited_at) |
| **medicines** | id, name, generic_name, form (tablet, capsule, syrup, injection, cream, drops, inhaler, suppository, other), strength, default_dose, default_frequency, default_duration, notes, is_active · idx(name) |
| **prescriptions** | id, visit_id (FK), patient_id (FK), doctor_id (FK), notes, issued_at |
| **prescription_items** | id, prescription_id (FK), medicine_id (FK nullable), medicine_name (snapshot), dose, frequency, duration, instructions, sort |
| **prescription_templates** | id, doctor_id (FK nullable = shared), name, notes |
| **prescription_template_items** | id, prescription_template_id (FK), medicine_id (FK nullable), medicine_name, dose, frequency, duration, instructions, sort |

### Lab & imaging (module)
| Table | Columns |
|---|---|
| **lab_tests** | id, name, type (lab, imaging), service_id (FK nullable), is_active |
| **lab_requests** | id, patient_id (FK), visit_id (FK nullable), doctor_id (FK), status (requested, sample_taken, completed, cancelled), notes, requested_at, completed_at |
| **lab_request_items** | id, lab_request_id (FK), lab_test_id (FK nullable), name, result_text |

Result files are stored in `patient_attachments.lab_request_id`.

### Billing
| Table | Columns |
|---|---|
| **services** | id, name_ar, name_en, category, price, is_active |
| **invoices** (SD) | id, number (unique), patient_id (FK), visit_id (FK nullable), appointment_id (FK nullable), doctor_id (FK nullable), issued_at, subtotal, discount_type (fixed/percent), discount_value, discount_amount, total, paid_amount, balance, status (draft, unpaid, partially_paid, paid, void), voided_at, void_reason, notes, created_by · idx(patient_id), idx(status, issued_at), idx(doctor_id, issued_at) |
| **invoice_items** | id, invoice_id (FK), service_id (FK nullable), description, quantity, unit_price, discount, total |
| **payments** | id, number (unique receipt no.), invoice_id (FK), patient_id (FK), amount, method (cash, card, instapay, wallet, bank_transfer), reference, paid_at, received_by (FK), notes · idx(paid_at), idx(method) |

Patient balance = Σ non-void invoice totals − Σ payments. Totals and statuses are recalculated inside a transaction by `InvoiceCalculator`. Paid invoices can only be voided, not edited.

### Expenses & inventory (modules)
| Table | Columns |
|---|---|
| **expense_categories** | id, name_ar, name_en, is_active |
| **expenses** | id, expense_category_id (FK), amount, spent_at, description, payment_method, receipt_path, created_by · idx(spent_at) |
| **inventory_items** | id, name, unit, quantity, reorder_level, unit_cost, is_active (lightweight stock module) |
| **stock_movements** | id, inventory_item_id (FK), type (in, out, adjust), quantity, reason, user_id |

### Audit & notifications
| Table | Columns |
|---|---|
| **activity_log** | Spatie activitylog — logs `viewed` / `updated` / `deleted` events on visits, prescriptions, patient attachments, invoices, payments, users, settings |
| **notification_logs** | id, appointment_id (FK nullable), patient_id (FK), channel (whatsapp_link, sms, …), recipient, message, status (prepared, sent, failed), sent_by, sent_at |

### Relations overview
```
User 1─1 DoctorProfile          Patient 1─* Appointment *─1 User(doctor)
Patient 1─* Visit 1─1 Appointment      Visit 1─* Prescription 1─* PrescriptionItem
Patient 1─* Invoice 1─* InvoiceItem    Invoice 1─* Payment
Patient 1─* PatientAttachment          Visit 1─* LabRequest 1─* LabRequestItem
VisitType *─1 Service                  Expense *─1 ExpenseCategory
```

### Enums (`app/Enums`)
AppointmentStatus (booked, confirmed, arrived, in_progress, completed, cancelled, no_show — with allowed-transition map), AppointmentSource, Gender, BloodType, InvoiceStatus, PaymentMethod, DiscountType, MedicineForm, AttachmentCategory, LabRequestStatus, LabTestType, CustomFieldType, ClinicSpecialty (general, dental, pediatrics, dermatology, ophthalmology, gynecology, orthopedics, ent, cardiology, internal), Module (inventory, lab, expenses), Role. Each enum has `label()` (translated), and `color()` where relevant, for the UI.

---

## 4. Roles & permissions

Permissions are granular (`patients.view`, `visits.view_medical`, `invoices.void`, …). Roles are seeded; Admin can edit role permissions in Settings.

| Area | Admin | Doctor | Receptionist | Accountant |
|---|---|---|---|---|
| Patients (demographics) | ✔ | own* | ✔ | view |
| Medical notes / visits / prescriptions | ✔ | own* | ✘ | ✘ |
| Attachments (medical) | ✔ | own* | upload only, cannot open X-ray/lab files | ✘ |
| Appointments & queue | ✔ | own schedule | ✔ | ✘ |
| Invoices & payments | ✔ | view own | create, take payment | ✔ (+ void) |
| Expenses | ✔ | ✘ | ✘ | ✔ |
| Reports | all | own | today's summary only | financial |
| Settings, users, activity log | ✔ | ✘ | ✘ | ✘ |

*own = scoped by policy (`doctor_id = auth user`), widened by the `doctors_share_records` setting.
Every model has a Policy; Livewire components call `$this->authorize()`; module middleware (`module:lab`) returns 404 when a module is disabled.

---

## 5. Folder structure

```
/ (repo root)
├── README.md               ← comparison of the 5 templates
├── PLAN.md
├── tools/sync-backend.ps1  ← propagate backend from template_1 → 2..5
├── template_1/ … template_5/
```
Inside each template (backend identical; only ★ dirs differ between templates):
```
app/
  Actions/          single-purpose operations (BookAppointment, CheckInPatient, RecordPayment, …)
  Services/         Settings, AppointmentScheduler, QueueService, InvoiceCalculator, NumberSequence,
                    ReportService, BackupService, Notifications/{MessageChannel interface, WhatsAppLinkChannel}
  Enums/  Models/  Policies/  Rules/ (EgyptianPhone)  Casts/ (Money)
  Http/Controllers/ (attachment download, print, PDF, export) · Http/Requests/ · Http/Middleware/ (SetLocale, EnsureModuleEnabled)
  Livewire/         component classes + Livewire Form objects (validation)
  Console/Commands/ ClinicInstall, ClinicBackup
  View/Components/  ★ (template-specific UI components)
config/clinic.php   defaults (specialties, modules, currency, phone regex)
database/migrations · seeders/ (Base: roles, settings, visit types, medicines…; Demo: Arabic fake data) · factories/
lang/ar · lang/en   (JSON + PHP files; identical in all templates)
resources/
  css/app.css ★  js/app.js ★
  views/layouts ★  views/components ★  views/livewire ★  views/print ★  views/auth ★
tests/Feature · tests/Unit
DESIGN.md ★   README.md (AR + EN)
```

---

## 6. The five designs

### template_1 — "Clinical Clean"
- **Palette:** white / slate-50 surfaces, teal-600 `#0D9488` primary, blue-600 `#2563EB` secondary, soft shadows, `rounded-xl`.
- **Fonts:** IBM Plex Sans Arabic + Inter. **Icons:** Heroicons (outline).
- **Layout:** fixed **right sidebar** (in RTL; mirrors in EN) with grouped nav; top bar with search + user menu.
- **Dashboard:** KPI card row, today's appointments card, revenue line chart, waiting-room mini card.
- **Calendar:** clean time grid, colored event cards per visit type, doctor tabs.
- **Patient profile:** header card (avatar initials, file no., age, allergy badges) + tabs (Overview, Visits, Prescriptions, Invoices, Attachments) + vertical timeline.
- **Login:** split screen, teal medical illustration panel + form card.
- **Print:** classic letterhead with logo top, teal rule line, Rx symbol, signature box bottom.

### template_2 — "Dark Pro"
- **Palette:** navy `#0B1220` / graphite `#161D2B` surfaces, emerald `#10B981` accent, sharp corners (`rounded-none`/`sm`), hairline borders; light-mode toggle (remembered per user).
- **Fonts:** Readex Pro (Arabic) + Manrope, JetBrains Mono for numbers/IDs. **Icons:** Lucide.
- **Layout:** collapsible **icon-only sidebar** (expands on hover/pin), command palette (Ctrl+K) for patient search, breadcrumb toolbar.
- **Dashboard:** dense grid of compact stat tiles with sparklines, data tables with sticky headers and inline status pills.
- **Calendar:** compact week view with doctors as **resource columns**, small chips.
- **Patient profile:** two-column — left meta panel (vitals trend, allergies), right tabbed data tables.
- **Login:** centered terminal-style dark card, emerald glow focus states.
- **Print:** monochrome, modern grid header, QR-style file number block, compact table invoice.

### template_3 — "Warm & Friendly"
- **Palette:** cream `#FFF8EE` background, coral `#FF6B5B` primary, soft orange `#FFA24C` secondary, mint for success, `rounded-3xl`, chunky soft shadows.
- **Fonts:** Baloo Bhaijaan 2 (Arabic, rounded) + Nunito. **Icons:** Phosphor (duotone) + emoji accents (🦷 👶 💊).
- **Layout:** **top horizontal navigation** with pill tabs, no sidebar; mobile → hamburger sheet.
- **Dashboard:** greeting hero ("صباح الخير د. أحمد ☀️"), big colorful tiles, illustrated empty states.
- **Calendar:** day view as large rounded "agenda cards"; week view as colorful columns.
- **Patient profile:** friendly hero card with large avatar/emoji by age & gender, sticker-like allergy tags, card-based timeline.
- **Login:** playful illustration, large inputs, coral button.
- **Print:** rounded header band in coral, friendly footer ("نتمنى لكم الشفاء العاجل"), child-friendly prescription layout.

### template_4 — "Luxury Minimal"
- **Palette:** pure white / near-black `#0A0A0A`, gold `#B8975A` accents, 1px thin borders, no heavy shadows, generous whitespace.
- **Fonts:** Headings Amiri (Arabic) + Cormorant Garamond; body Almarai + Jost. **Icons:** Tabler (thin stroke).
- **Layout:** slim top bar + **minimal left/right text menu** (no icons in nav), wide margins, content max-width.
- **Dashboard:** editorial layout — large serif numerals, few metrics, thin-line charts, agenda list.
- **Calendar:** elegant list/agenda by day with thin time rulers; week as minimal grid with gold "now" line.
- **Patient profile:** magazine-style header (serif name, gold divider), sections stacked vertically instead of tabs.
- **Login:** full-screen black with centered gold monogram (from clinic logo/initials).
- **Print:** elegant letterhead, gold hairlines, serif title, wide margins.

### template_5 — "Tablet-First Reception"
- **Palette:** indigo `#4F46E5` / purple `#7C3AED`, high-contrast white & slate-900 text, AA+ contrast, large status colors.
- **Fonts:** Cairo (Arabic) + Lexend. **Icons:** Heroicons (solid, large).
- **Layout:** **bottom tab bar** on phone/tablet, slim rail on desktop; **split screen** everywhere — queue/list on one side, details on the other. Min 48–56 px touch targets.
- **Dashboard:** "Reception board": live queue column + now-serving panel + quick actions (New patient, Walk-in, Take payment) as big buttons.
- **Calendar:** horizontally scrollable day timeline with big tappable slots; tap empty slot → book.
- **Patient profile:** right panel slide-over with big action buttons (Book, Check-in, Pay, WhatsApp).
- **Login:** PIN-pad-style large inputs, user avatars quick-pick (email still required on first login).
- **Print:** high-contrast bold header with indigo band; receipt also in 80 mm thermal format.

Each template's `DESIGN.md` documents colors (tokens), fonts, spacing scale, radii, shadows, components and print rules.

---

## 7. Phase plan

| # | Phase | Deliverable |
|---|---|---|
| 1 | Plan | this document ✅ |
| 2 | T1 foundation | Laravel + Livewire + Tailwind setup, auth, roles/permissions, Settings (info, branding, color override, modules, specialty), RTL/AR+EN switch, layout shell, `clinic:install`, base seeders, tests |
| 3 | T1 patients | CRUD, file numbering, search, profile + timeline, attachments (secure), custom fields on patients |
| 4 | T1 scheduling | working hours, blocked periods, visit types, booking with conflict checks, day/week calendar, walk-ins, status transitions, waiting room/queue |
| 5 | T1 medical | visits (vitals/BMI/custom fields/follow-up), medicines, prescriptions & templates, lab requests, A5 prescription print |
| 6 | T1 billing | services, invoices, discounts, partial payments, balances, receipts, PDF, expenses, inventory |
| 7 | T1 finish | dashboard, reports + Excel/PDF export, WhatsApp reminders, activity log viewer, backups + schedule, demo seeder, MySQL run, README (AR/EN) |
| 8–11 | T2–T5 | copy backend, new presentation layer + DESIGN.md + README, all tests green |
| 12 | Final review | permission/security audit across all five, root comparison README, sync script |

**Required feature tests (minimum):** booking conflicts (overlap, adjacent OK, cancelled frees the slot, blocked period, outside working hours); receptionist cannot see medical notes (Livewire + routes + attachment download); invoice totals/discounts/partial payments/balance/void; install command; module toggles returning 404; doctor cannot access another doctor's visit when sharing is off.
