<div>
    <p class="eyebrow">{{ __('Members only') }}</p>
    <h1 class="mt-3 font-serif text-5xl leading-tight text-ink">{{ __('Welcome back') }}</h1>
    <div class="gold-rule mt-6"></div>
    <p class="mt-6 text-[15px] text-muted">{{ __('Log in to manage your clinic.') }}</p>

    @if (session('status'))
        <div class="mt-8 border-s border-primary-500 ps-4 text-sm text-primary-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="mt-10 space-y-8">
        <x-field :label="__('E-mail')" for="email">
            <input wire:model="email" id="email" type="email" autocomplete="username" autofocus dir="ltr"
                   @class(['form-control text-start', 'is-invalid' => $errors->has('email')])>
        </x-field>

        <x-field for="password">
            <div class="flex items-center justify-between">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" wire:navigate class="mb-2 text-xs text-muted underline-offset-4 hover:text-primary-700 hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <div x-data="{ show: false }" class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" dir="ltr"
                       @class(['form-control pe-10 text-start', 'is-invalid' => $errors->has('password')])>
                <button type="button" x-on:click="show = !show" class="absolute inset-y-0 end-0 grid w-8 place-items-center text-neutral-400 hover:text-ink" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                    <x-icon name="eye" class="size-4" />
                </button>
            </div>
        </x-field>

        <label class="flex items-center gap-3 text-[13px] text-neutral-600">
            <input wire:model="remember" type="checkbox" class="form-check"> {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn btn-primary w-full py-4" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
            <span wire:loading wire:target="login">{{ __('Logging in…') }}</span>
        </button>
    </form>
</div>
