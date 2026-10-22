<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Log in')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $key = mb_strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout(request()));

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        $credentials = ['email' => mb_strtolower(trim($this->email)), 'password' => $this->password];

        if (! Auth::attempt($credentials + ['is_active' => true], $this->remember)) {
            RateLimiter::hit($key, 60);

            $inactive = User::query()->where('email', $credentials['email'])->where('is_active', false)->exists();

            throw ValidationException::withMessages([
                'email' => $inactive ? __('Your account has been deactivated.') : __('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
