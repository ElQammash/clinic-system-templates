<?php

namespace App\Livewire\Forms;

use App\Enums\ClinicSpecialty;
use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ClinicSettingsForm extends Form
{
    // Identity & contact
    public string $clinic_name = '';

    public ?string $clinic_name_en = null;

    public ?string $clinic_tagline = null;

    public ?string $clinic_tagline_en = null;

    public string $specialty = 'general';

    public ?string $phone = null;

    public ?string $phone_alt = null;

    public ?string $whatsapp = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $address_en = null;

    // Branding
    public ?string $primary_color = null;

    // Regional
    public string $default_locale = 'ar';

    public string $currency_code = 'EGP';

    public string $currency_symbol = 'ج.م';

    public ?string $currency_symbol_en = 'EGP';

    public string $digits = 'western';

    public string $phone_format = 'egypt';

    // Modules & records
    public bool $module_lab = true;

    public bool $module_expenses = true;

    public bool $module_inventory = false;

    public bool $doctors_share_records = true;

    // Printing
    public ?string $invoice_footer = null;

    public ?string $invoice_footer_en = null;

    public ?string $prescription_footer = null;

    public ?string $prescription_footer_en = null;

    public bool $print_show_logo = true;

    // Messages
    public string $reminder_template = '';

    public function fillFromSettings(): void
    {
        foreach ($this->keys() as $key) {
            $value = settings($key);

            if (is_bool($this->{$key})) {
                $this->{$key} = (bool) $value;
            } elseif ($value !== null) {
                $this->{$key} = (string) $value;
            }
        }
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->rules());
    }

    public function rules(): array
    {
        $phone = ['nullable', 'string', new PhoneNumber($this->phone_format)];

        return [
            'clinic_name' => ['required', 'string', 'max:150'],
            'clinic_name_en' => ['nullable', 'string', 'max:150'],
            'clinic_tagline' => ['nullable', 'string', 'max:200'],
            'clinic_tagline_en' => ['nullable', 'string', 'max:200'],
            'specialty' => ['required', Rule::enum(ClinicSpecialty::class)],
            'phone' => $phone,
            'phone_alt' => $phone,
            'whatsapp' => $phone,
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:300'],
            'address_en' => ['nullable', 'string', 'max:300'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'default_locale' => ['required', Rule::in(array_keys(config('clinic.locales')))],
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_symbol_en' => ['nullable', 'string', 'max:10'],
            'digits' => ['required', Rule::in(['western', 'arabic'])],
            'phone_format' => ['required', Rule::in(array_keys(config('clinic.phone_patterns')))],
            'module_lab' => ['boolean'],
            'module_expenses' => ['boolean'],
            'module_inventory' => ['boolean'],
            'doctors_share_records' => ['boolean'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'invoice_footer_en' => ['nullable', 'string', 'max:500'],
            'prescription_footer' => ['nullable', 'string', 'max:500'],
            'prescription_footer_en' => ['nullable', 'string', 'max:500'],
            'print_show_logo' => ['boolean'],
            'reminder_template' => ['required', 'string', 'max:1000'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'clinic_name' => __('Clinic name'),
            'phone' => __('Phone'),
            'phone_alt' => __('Alternative phone'),
            'whatsapp' => __('WhatsApp number'),
            'primary_color' => __('Primary color'),
        ];
    }

    /** @return array<string, mixed> validated values, phones normalised, currency upper-cased */
    public function values(): array
    {
        $data = $this->validate();

        foreach (['phone', 'phone_alt', 'whatsapp'] as $key) {
            $data[$key] = PhoneNumber::normalize($data[$key]);
        }

        $data['currency_code'] = strtoupper($data['currency_code']);
        $data['primary_color'] = filled($data['primary_color']) ? strtolower($data['primary_color']) : null;

        return $data;
    }
}
