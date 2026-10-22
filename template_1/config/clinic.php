<?php

use App\Services\Messaging\WhatsAppLinkChannel;

/*
|--------------------------------------------------------------------------
| Clinic template defaults
|--------------------------------------------------------------------------
|
| Nothing here is clinic-specific: these are the defaults used on a fresh
| install. Every value under "settings" can be changed by the clinic owner
| from the Settings page (stored in the `settings` table and cached).
|
*/

return [

    'locales' => [
        'ar' => ['name' => 'العربية', 'dir' => 'rtl'],
        'en' => ['name' => 'English', 'dir' => 'ltr'],
    ],

    'phone_patterns' => [
        // Egyptian mobile numbers: 010, 011, 012, 015 + 8 digits.
        'egypt' => '/^01[0125][0-9]{8}$/',
        // Any international-looking number (7–15 digits, optional leading +).
        'any' => '/^\+?[0-9]{7,15}$/',
    ],

    'country_calling_code' => '20',

    'logo' => [
        'disk' => 'local',
        'directory' => 'branding',
        'max_kb' => 1024,
    ],

    'attachments' => [
        'disk' => 'local', // private disk: files are served only through an authorised route
        'max_kb' => 20480,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'heic', 'pdf', 'dcm', 'doc', 'docx'],
    ],

    /*
     | Messaging: how reminders reach patients. "whatsapp_link" is free (prefilled wa.me chat).
     | Add your own provider class implementing App\Contracts\MessageChannel and select it here.
     */
    'messaging' => [
        'channel' => env('CLINIC_MESSAGING_CHANNEL', 'whatsapp_link'),
        'channels' => [
            'whatsapp_link' => WhatsAppLinkChannel::class,
        ],
    ],

    'backups' => [
        'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),
    ],

    'settings' => [
        // Clinic identity
        'clinic_name' => 'عيادتي',
        'clinic_name_en' => 'My Clinic',
        'clinic_tagline' => null,
        'clinic_tagline_en' => null,
        'specialty' => 'general',
        'phone' => null,
        'phone_alt' => null,
        'whatsapp' => null,
        'email' => null,
        'address' => null,
        'address_en' => null,

        // Branding
        'logo_path' => null,
        'primary_color' => null, // null = use the template's own palette

        // Regional
        'default_locale' => 'ar',
        'currency_code' => 'EGP',
        'currency_symbol' => 'ج.م',
        'currency_symbol_en' => 'EGP',
        'digits' => 'western', // western | arabic
        'phone_format' => 'egypt', // egypt | any

        // Optional modules
        'module_lab' => true,
        'module_expenses' => true,
        'module_inventory' => false,

        // Patients & medical records
        'file_number_prefix' => '',
        'doctors_share_records' => true,

        // Printing
        'invoice_footer' => 'شكراً لزيارتكم',
        'invoice_footer_en' => 'Thank you for your visit',
        'prescription_footer' => null,
        'prescription_footer_en' => null,
        'print_show_logo' => true,

        // Messages (placeholders: {patient} {doctor} {date} {day} {time} {clinic} {phone} {address})
        'reminder_template' => "مرحبًا {patient} 👋\nنذكّرك بموعدك في {clinic} مع {doctor}\nيوم {day} {date} الساعة {time}.\nللإلغاء أو التعديل برجاء الاتصال على {phone}.\nنتمنى لك دوام الصحة 🌿",
    ],

];
