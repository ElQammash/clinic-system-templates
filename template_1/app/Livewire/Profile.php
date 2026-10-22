<?php

namespace App\Livewire;

use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My profile')]
class Profile extends Component
{
    public string $name = '';

    public ?string $phone = null;

    public string $locale = 'ar';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->phone = $user->phone;
        $this->locale = $user->preferredLocale();
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', new PhoneNumber],
            'locale' => ['required', Rule::in(array_keys(config('clinic.locales')))],
        ]);

        $user = auth()->user();
        $localeChanged = $user->locale !== $data['locale'];

        $user->update([
            'name' => $data['name'],
            'phone' => PhoneNumber::normalize($data['phone']),
            'locale' => $data['locale'],
        ]);

        if ($localeChanged) {
            session()->flash('toast', __('Profile updated.'));
            $this->redirectRoute('profile');

            return;
        }

        $this->dispatch('toast', message: __('Profile updated.'));
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update(['password' => $this->password]);
        activity('auth')->causedBy(auth()->user())->performedOn(auth()->user())->event('password_changed')->log('password changed');

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('toast', message: __('Password changed.'));
    }

    public function render()
    {
        return view('livewire.profile');
    }
}
