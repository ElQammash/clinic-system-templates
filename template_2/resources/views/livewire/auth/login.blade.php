<div>
    <div class="mb-6">
        <p class="font-mono text-[11px] tracking-widest text-primary-400 uppercase">{{ __('Secure sign-in') }}</p>
        <h1 class="mt-1 text-xl font-semibold text-ink">{{ __('Welcome back') }}</h1>
        <p class="mt-1 text-xs text-muted">{{ __('Log in to manage your clinic.') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-sm border border-primary-500/30 bg-primary-500/10 px-3 py-2 text-xs text-primary-400">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <x-field :label="__('E-mail')" for="email">
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input wire:model="email" id="email" type="email" autocomplete="username" autofocus dir="ltr"
                       @class(['form-control ps-8 text-start font-mono', 'is-invalid' => $errors->has('email')])>
            </div>
        </x-field>

        <x-field for="password">
            <div class="mb-1 flex items-center justify-between">
                <label for="password" class="form-label mb-0">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-[11px] text-primary-400 hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <div x-data="{ show: false }" class="relative">
                <x-icon name="lock" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" dir="ltr"
                       @class(['form-control ps-8 pe-9 text-start font-mono', 'is-invalid' => $errors->has('password')])>
                <button type="button" x-on:click="show = !show" class="absolute inset-y-0 end-0 grid w-9 place-items-center text-muted hover:text-ink" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                    <x-icon name="eye" class="size-4" />
                </button>
            </div>
        </x-field>

        <label class="flex items-center gap-2 text-xs text-ink-soft">
            <input wire:model="remember" type="checkbox" class="form-check"> {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
            <span wire:loading wire:target="login">{{ __('Logging in…') }}</span>
        </button>
    </form>
</div>
