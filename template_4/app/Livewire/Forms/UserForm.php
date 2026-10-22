<?php

namespace App\Livewire\Forms;

use App\Enums\ClinicSpecialty;
use App\Enums\Role;
use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $password = null;

    public string $role = 'receptionist';

    public ?string $locale = null;

    public bool $is_active = true;

    // Doctor profile
    public ?string $title_ar = null;

    public ?string $title_en = null;

    public ?string $specialty = null;

    public string $color = '#0d9488';

    public int $slot_minutes = 15;

    public float|int|string $consultation_fee = 0;

    public ?string $license_number = null;

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->password = null;
        $this->role = $user->primaryRole()?->value ?? Role::Receptionist->value;
        $this->locale = $user->locale;
        $this->is_active = $user->is_active;

        $profile = $user->doctorProfile;
        $this->title_ar = $profile?->title_ar;
        $this->title_en = $profile?->title_en;
        $this->specialty = $profile?->specialty?->value;
        $this->color = $profile?->color ?? '#0d9488';
        $this->slot_minutes = $profile?->slot_minutes ?? 15;
        $this->consultation_fee = $profile?->consultation_fee ?? 0;
        $this->license_number = $profile?->license_number;
    }

    public function rules(): array
    {
        $isDoctor = $this->role === Role::Doctor->value;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', new PhoneNumber],
            'password' => [$this->user ? 'nullable' : 'required', Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
            'locale' => ['nullable', Rule::in(array_keys(config('clinic.locales')))],
            'is_active' => ['boolean'],
            'title_ar' => ['nullable', 'string', 'max:150'],
            'title_en' => ['nullable', 'string', 'max:150'],
            'specialty' => ['nullable', Rule::enum(ClinicSpecialty::class)],
            'color' => [$isDoctor ? 'required' : 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'slot_minutes' => [$isDoctor ? 'required' : 'nullable', 'integer', 'min:5', 'max:240'],
            'consultation_fee' => [$isDoctor ? 'required' : 'nullable', 'numeric', 'min:0', 'max:1000000'],
            'license_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => __('Name'),
            'phone' => __('Phone'),
            'slot_minutes' => __('Slot duration'),
            'consultation_fee' => __('Consultation fee'),
        ];
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} [user data, doctor profile] */
    public function payload(): array
    {
        $data = $this->validate();

        return [
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => PhoneNumber::normalize($data['phone']),
                'password' => $data['password'],
                'role' => $data['role'],
                'locale' => $data['locale'] ?: null,
                'is_active' => $data['is_active'],
            ],
            [
                'title_ar' => $data['title_ar'],
                'title_en' => $data['title_en'],
                'specialty' => $data['specialty'] ?: null,
                'color' => strtolower((string) $data['color']),
                'slot_minutes' => (int) $data['slot_minutes'],
                'consultation_fee' => (float) $data['consultation_fee'],
                'license_number' => $data['license_number'],
            ],
        ];
    }
}
