<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public ?string $status = null;

    public function sendLink(): void
    {
        $this->validate();

        Password::sendResetLink(['email' => mb_strtolower(trim($this->email))]);

        // Same message whether or not the e-mail exists (no account enumeration).
        $this->status = __('If this e-mail belongs to an account, a reset link has been sent.');
        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
