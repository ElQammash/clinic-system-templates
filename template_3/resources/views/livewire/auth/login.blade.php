<div>
    <div class="text-center">
        <span class="emoji inline-block animate-[wave_1.8s_ease-in-out_infinite] text-5xl" aria-hidden="true">👋</span>
        <h1 class="mt-2 text-3xl font-extrabold text-ink">{{ __('Welcome back') }}</h1>
        <p class="mt-1 text-[15px] text-muted">{{ __('Log in to manage your clinic.') }}</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="mt-7 space-y-5">
        <x-field :label="__('E-mail')" for="email">
            <input wire:model="email" id="email" type="email" autocomplete="username" autofocus dir="ltr"
                   @class(['form-control py-3 text-start', 'is-invalid' => $errors->has('email')])>
        </x-field>

        <x-field for="password">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="form-label mb-0">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-bold text-primary-600 hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <div x-data="{ show: false }" class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" dir="ltr"
                       @class(['form-control py-3 pe-12 text-start', 'is-invalid' => $errors->has('password')])>
                <button type="button" x-on:click="show = !show" class="absolute inset-y-0 end-0 grid w-12 place-items-center text-lg" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                    <span class="emoji" x-text="show ? '🙈' : '👀'"></span>
                </button>
            </div>
        </x-field>

        <label class="flex items-center gap-2 text-[15px] font-semibold text-stone-600">
            <input wire:model="remember" type="checkbox" class="form-check"> {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn btn-primary w-full py-3.5 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
            <span wire:loading wire:target="login">{{ __('Logging in…') }}</span>
        </button>
    </form>
</div>
