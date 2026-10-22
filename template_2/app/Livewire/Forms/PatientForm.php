<?php

namespace App\Livewire\Forms;

use App\Enums\BloodType;
use App\Enums\CustomFieldEntity;
use App\Enums\Gender;
use App\Models\Patient;
use App\Rules\PhoneNumber;
use App\Services\CustomFields;
use App\Support\Format;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PatientForm extends Form
{
    public ?Patient $patient = null;

    public string $name = '';

    public ?string $phone = null;

    public ?string $phone_alt = null;

    public ?string $gender = null;

    /** "date" = exact birth date, "age" = approximate age in years */
    public string $birthMode = 'date';

    public ?string $birth_date = null;

    public ?string $age_years = null;

    public ?string $national_id = null;

    public ?string $address = null;

    public ?string $occupation = null;

    public ?string $blood_type = null;

    public ?string $allergies = null;

    public ?string $chronic_diseases = null;

    public ?string $notes = null;

    /** @var array<string, mixed> */
    public array $custom = [];

    public function setPatient(Patient $patient): void
    {
        $this->patient = $patient;
        $this->name = $patient->name;
        $this->phone = $patient->phone;
        $this->phone_alt = $patient->phone_alt;
        $this->gender = $patient->gender?->value;
        $this->birthMode = $patient->birth_date_is_estimated ? 'age' : 'date';
        $this->birth_date = $patient->birth_date?->format('Y-m-d');
        $this->age_years = $patient->birth_date_is_estimated ? (string) $patient->ageInYears() : null;
        $this->national_id = $patient->national_id;
        $this->address = $patient->address;
        $this->occupation = $patient->occupation;
        $this->blood_type = $patient->blood_type?->value;
        $this->allergies = $patient->allergies;
        $this->chronic_diseases = $patient->chronic_diseases;
        $this->notes = $patient->notes;
        $this->custom = $patient->custom_data ?? [];
    }

    /** Accept Arabic-Indic digits and stray spaces in number-like fields. */
    protected function normalizeInput(): void
    {
        foreach (['phone', 'phone_alt', 'national_id', 'age_years'] as $key) {
            $this->{$key} = filled($this->{$key}) ? Format::normalizeDigits(trim((string) $this->{$key})) : null;
        }
    }

    public function rules(): array
    {
        $nationalId = settings('phone_format') === 'egypt'
            ? ['nullable', 'digits:14']
            : ['nullable', 'string', 'max:20'];

        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'phone' => ['nullable', new PhoneNumber],
            'phone_alt' => ['nullable', new PhoneNumber],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birthMode' => ['required', Rule::in(['date', 'age'])],
            'birth_date' => ['nullable', 'required_if:birthMode,date', 'exclude_if:birthMode,age', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'age_years' => ['nullable', 'exclude_if:birthMode,date', 'integer', 'min:0', 'max:120'],
            'national_id' => [...$nationalId, Rule::unique('patients', 'national_id')->ignore($this->patient?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_diseases' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...app(CustomFields::class)->rules(CustomFieldEntity::Patient),
        ];
    }

    protected function messages(): array
    {
        return [
            'birth_date.required_if' => __('Enter the birth date, or switch to age.'),
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => __('Patient name'),
            'phone' => __('Phone'),
            'phone_alt' => __('Alternative phone'),
            'birth_date' => __('Birth date'),
            'age_years' => __('Age'),
            'national_id' => __('National ID'),
            ...app(CustomFields::class)->attributes(CustomFieldEntity::Patient),
        ];
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} [attributes, custom field input] */
    public function payload(): array
    {
        $this->normalizeInput();
        $data = $this->validate();

        $estimated = $data['birthMode'] === 'age' && filled($data['age_years'] ?? null);
        $birthDate = $estimated
            ? now()->subYears((int) $data['age_years'])->startOfYear()->format('Y-m-d')
            : ($data['birth_date'] ?? null);

        return [
            [
                'name' => trim(preg_replace('/\s+/u', ' ', $data['name'])),
                'phone' => PhoneNumber::normalize($data['phone']),
                'phone_alt' => PhoneNumber::normalize($data['phone_alt']),
                'gender' => $data['gender'] ?: null,
                'birth_date' => $birthDate,
                'birth_date_is_estimated' => $estimated,
                'national_id' => $data['national_id'] ?: null,
                'address' => $data['address'],
                'occupation' => $data['occupation'],
                'blood_type' => $data['blood_type'] ?: null,
                'allergies' => $data['allergies'],
                'chronic_diseases' => $data['chronic_diseases'],
                'notes' => $data['notes'],
            ],
            $data['custom'] ?? [],
        ];
    }
}
