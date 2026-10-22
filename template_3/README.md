# نظام إدارة العيادة — القالب 3 «Warm & Friendly»
# Clinic Management System — Template 3 "Warm & Friendly"

[العربية](#العربية) · [English](#english)

---

## العربية

نظام متكامل لإدارة العيادات: المرضى، المواعيد وغرفة الانتظار، الكشوفات والروشتات، التحاليل والأشعة، الفواتير والمدفوعات، المصروفات والمخزون، التقارير، وتذكيرات واتساب. واجهة عربية بالكامل (من اليمين لليسار) مع دعم الإنجليزية لكل مستخدم.

هذا القالب بتصميم **Warm & Friendly**: ألوان دافئة (كريمي، مرجاني، برتقالي)، قائمة علوية أفقية، خطوط كبيرة ودودة، أيقونات إيموجي وحواف دائرية جدًا — مناسب لعيادات الأطفال والأسنان وطب الأسرة. التفاصيل في [DESIGN.md](DESIGN.md).

### المتطلبات

| المكوّن | الإصدار |
|---|---|
| PHP | 8.4 أو أحدث، مع الإضافات: `intl`, `gd`, `zip`, `pdo_mysql`, `mbstring`, `fileinfo` |
| Composer | 2.x |
| Node.js | 20 أو أحدث (للبناء فقط، لا يلزم على السيرفر) |
| قاعدة البيانات | MySQL 8 أو MariaDB 10.4+ |

### التثبيت (خطوة بخطوة)

```bash
composer install
npm install && npm run build
cp .env.example .env            # ثم اضبط DB_DATABASE و DB_USERNAME و DB_PASSWORD
php artisan clinic:install      # يسأل عن اسم العيادة وبيانات المدير ثم يجهز قاعدة البيانات
```

أمر `clinic:install` يقوم بـ: إنشاء مفتاح التطبيق إن لم يوجد، تشغيل الـ migrations، إضافة الأدوار والصلاحيات، مواعيد العمل الافتراضية، أنواع الكشف، قائمة الأدوية والتحاليل، الخدمات والأسعار، والحقول المقترحة حسب تخصص العيادة، ثم إنشاء حساب المدير.

بدون أسئلة (مناسب للسكريبتات):

```bash
php artisan clinic:install --clinic-name="عيادة الشفاء" --specialty=pediatrics \
  --admin-name="د. أحمد" --email=admin@clinic.com --password=Secret123 --force
```

**بيانات تجريبية للعرض على العملاء:** أضف `--demo` لتحميل ~80 مريضًا و1400 موعد و400 كشف و900 فاتورة بأسماء مصرية. حسابات العرض: `doctor@demo.test` و `reception@demo.test` و `accountant@demo.test` بكلمة المرور `password`.

### تشغيله لعيادة جديدة (النشر)

1. انسخ المشروع على السيرفر (أو ارفع الملفات بعد `composer install --no-dev` و `npm run build`).
2. اجعل مجلد الموقع يشير إلى `public/`.
3. اضبط `.env`: ‏`APP_ENV=production` و `APP_DEBUG=false` و `APP_URL` وبيانات قاعدة البيانات والبريد.
4. شغّل `php artisan clinic:install` ثم `php artisan optimize`.
5. أضف مهمة مجدولة واحدة (cron) تعمل كل دقيقة — مطلوبة للنسخ الاحتياطي اليومي:
   ```
   * * * * * cd /path/to/clinic && php artisan schedule:run >> /dev/null 2>&1
   ```
   على ويندوز: استخدم Task Scheduler لتشغيل `php artisan schedule:run` كل دقيقة.
6. صلاحيات الكتابة لمجلدي `storage/` و `bootstrap/cache/`.

> ملفات المرضى (الأشعة والتحاليل) تُحفظ في `storage/app/private` ولا يمكن فتحها إلا بعد التحقق من الصلاحية. لا تجعل هذا المجلد عامًا.

### تغيير الهوية (Rebrand)

كل ذلك من **الإعدادات** بدون أي برمجة:
- **بيانات العيادة**: الاسم بالعربي والإنجليزي، الوصف، الهاتف، واتساب، العنوان.
- **الهوية البصرية**: رفع الشعار واختيار اللون الأساسي (يتغير لون النظام والمطبوعات فورًا).
- **اللغة والعملة**: اللغة الافتراضية، رمز العملة، الأرقام العربية أو الإنجليزية، صيغة أرقام الهاتف.
- **التخصص والوحدات**: نوع العيادة، تشغيل/إيقاف التحاليل والمصروفات والمخزون.
- **الطباعة والرسائل**: تذييل الفاتورة والروشتة، ونص رسالة تذكير واتساب.

### إضافة حقول مخصصة

**الإعدادات ← الحقول المخصصة**: أضف حقولًا لملف المريض أو لنموذج الكشف (نص، رقم، تاريخ، اختيار، اختيار متعدد، نعم/لا)، واجعلها إجبارية أو خاصة بتخصص معين. يوجد زر «إضافة الحقول المقترحة» لكل تخصص (أسنان، أطفال، رمد، جلدية، نساء…).

### الأدوار والصلاحيات

| الدور | يستطيع |
|---|---|
| مدير العيادة | كل شيء |
| طبيب | جدوله ومرضاه، الكشوفات والروشتات والتحاليل، تقاريره الخاصة |
| استقبال | المرضى، المواعيد، غرفة الانتظار، الفواتير والتحصيل — **بدون أي اطلاع على الملاحظات الطبية** |
| محاسب | الفواتير، المدفوعات، المصروفات، التقارير المالية |

يمكن تعديل صلاحيات كل دور من **الأدوار والصلاحيات**. كل فتح لسجل طبي أو ملف أشعة، وكل حذف فاتورة أو دفعة، يُسجل في **سجل النشاط**.

### النسخ الاحتياطي

نسخة يومية تلقائية الساعة 2:00 (قاعدة البيانات + ملفات المرضى + الشعار) تُحفظ في `storage/app/private/clinic-backups`، ويمكن التنزيل والنسخ اليدوي من **الإعدادات ← النسخ الاحتياطي**.
- يحتاج أداة `mysqldump`. على ويندوز/XAMPP أضف في `.env`: ‏`DB_DUMP_BINARY_PATH=C:/xampp/mysql/bin`
- لتنبيهات الفشل بالبريد: `BACKUP_NOTIFY_EMAIL=you@example.com`
- يُنصح بتنزيل نسخة بانتظام وحفظها خارج جهاز العيادة.

### تذكيرات واتساب

من صفحة **التذكيرات** (أو من نافذة الموعد) يفتح زر واتساب المحادثة والرسالة جاهزة بالعربية — بدون أي خدمة مدفوعة. لربط مزود رسائل لاحقًا (SMS أو WhatsApp API): أنشئ كلاسًا ينفذ `App\Contracts\MessageChannel` وسجّله في `config/clinic.php` تحت `messaging.channels` ثم اختره بـ `CLINIC_MESSAGING_CHANNEL` في `.env`.

### الاختبارات

```bash
php artisan test     # أكثر من 180 اختبارًا (تعمل على SQLite في الذاكرة)
```

---

## English

A complete clinic management system: patients, appointments & waiting room, visits & prescriptions, lab & imaging, invoices & payments, expenses & inventory, reports, and WhatsApp reminders. Fully Arabic (RTL) by default, with English per user.

This template uses the **Warm & Friendly** design: cream, coral and orange, a horizontal top navigation, large friendly type, emoji icons and very rounded shapes — a good fit for pediatric, dental and family clinics. See [DESIGN.md](DESIGN.md).

### Requirements

| Component | Version |
|---|---|
| PHP | 8.4+ with `intl`, `gd`, `zip`, `pdo_mysql`, `mbstring`, `fileinfo` |
| Composer | 2.x |
| Node.js | 20+ (build only, not needed on the server) |
| Database | MySQL 8 or MariaDB 10.4+ |

### Install

```bash
composer install
npm install && npm run build
cp .env.example .env            # then set DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan clinic:install      # asks for the clinic name and admin account, then sets up the database
```

`clinic:install` generates the app key if missing, runs the migrations, seeds roles & permissions, default working hours, visit types, the medicines and lab-test libraries, services & prices, and the suggested custom fields for the clinic type, then creates the admin account. Non-interactive:

```bash
php artisan clinic:install --clinic-name="Al Shifa Clinic" --specialty=dental \
  --admin-name="Dr. Ahmed" --email=admin@clinic.com --password=Secret123 --force
```

**Demo data for sales demos:** add `--demo` (≈80 patients, 1,400 appointments, 400 visits, 900 invoices with Egyptian names). Demo logins: `doctor@demo.test`, `reception@demo.test`, `accountant@demo.test`, password `password`.

### Deploy for a new clinic

1. Copy the project to the server (or upload after `composer install --no-dev` and `npm run build`).
2. Point the web root to `public/`.
3. In `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, database and mail settings.
4. Run `php artisan clinic:install`, then `php artisan optimize`.
5. Add one cron entry (needed for the daily backup):
   `* * * * * cd /path/to/clinic && php artisan schedule:run >> /dev/null 2>&1`
   On Windows, use Task Scheduler to run `php artisan schedule:run` every minute.
6. Make `storage/` and `bootstrap/cache/` writable.

Patient files (X-rays, lab results) live in `storage/app/private` and are only served after a permission check — never expose that folder publicly.

### Rebrand

Everything is in **Settings**, no code: clinic name (Arabic/English), tagline, phones, WhatsApp, address; logo and primary color (applied instantly to the UI and printouts); default language, currency symbol, Arabic or Western digits, phone format; clinic type and optional modules (lab, expenses, inventory); invoice / prescription footers and the WhatsApp reminder text.

### Add custom fields

**Settings → Custom fields**: add fields to the patient file or the visit form (text, number, date, single/multiple choice, yes/no), optionally required or limited to one clinic type. "Add suggested fields" inserts ready-made fields for dental, pediatrics, ophthalmology, dermatology, gynecology…

### Roles

| Role | Can |
|---|---|
| Admin | everything |
| Doctor | own schedule & patients, visits, prescriptions, lab requests, own reports |
| Receptionist | patients, appointments, waiting room, invoices & payments — **no access to medical notes** |
| Accountant | invoices, payments, expenses, financial reports |

Permissions per role are editable in **Roles & permissions**. Opening a medical record or file, deleting invoices or payments, logins and exports are recorded in the **Activity log**.

### Backups

Automatic daily backup at 02:00 (database + patient files + logo) in `storage/app/private/clinic-backups`; download or run manually from **Settings → Backups**.
- Requires `mysqldump`. On Windows/XAMPP set `DB_DUMP_BINARY_PATH=C:/xampp/mysql/bin` in `.env`.
- Failure e-mails: `BACKUP_NOTIFY_EMAIL=you@example.com`.

### WhatsApp reminders

The **Reminders** page (and the appointment window) opens WhatsApp with an Arabic message ready to send — no paid API. To plug in a provider later (SMS gateway, WhatsApp Business API), implement `App\Contracts\MessageChannel`, register it in `config/clinic.php` under `messaging.channels`, and select it with `CLINIC_MESSAGING_CHANNEL`.

### Architecture (for developers)

- Business logic: `app/Actions` and `app/Services` (booking, billing, reports…). Livewire components only validate and call them.
- Authorization: Policies in `app/Policies` + permissions from `App\Enums\Permission`.
- Money is stored as integer piastres; `App\Services\Billing\InvoiceCalculator` does all invoice math.
- All five templates share this backend; only `resources/views`, `resources/css`, `resources/js`, `vite.config.js` and `DESIGN.md` differ.

### Tests

```bash
php artisan test     # 180+ Pest tests (in-memory SQLite)
```
