<div>
    <h1 class="text-4xl font-extrabold text-ink">{{ __('Welcome back') }}</h1>
    <p class="mt-2 text-lg text-muted">{{ __('Log in to manage your clinic.') }}</p>

    @if (session('status'))
        <div class="mt-6 rounded-2xl bg-primary-50 px-5 py-4 text-base font-bold text-primary-900">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="mt-8 space-y-6">
        <x-field :label="__('E-mail')" for="email">
            <input wire:model="email" id="email" type="email" autocomplete="username" autofocus dir="ltr" inputmode="email"
                   @class(['form-control min-h-16 text-start text-lg', 'is-invalid' => $errors->has('email')])>
        </x-field>

        <x-field for="password">
            <div class="mb-2 flex items-center justify-between">
                <label for="password" class="form-label mb-0">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-base font-bold text-primary-700 hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <div x-data="{ show: false }" class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" dir="ltr"
                       @class(['form-control min-h-16 pe-16 text-start text-lg', 'is-invalid' => $errors->has('password')])>
                <button type="button" x-on:click="show = !show" class="absolute inset-y-2 end-2 grid w-12 place-items-center rounded-xl text-slate-600 hover:bg-slate-100" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                    <x-icon name="eye" class="size-6" />
                </button>
            </div>
        </x-field>

        <label class="flex min-h-12 items-center gap-3 text-lg font-semibold text-ink">
            <input wire:model="remember" type="checkbox" class="form-check"> {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
            <span wire:loading wire:target="login">{{ __('Logging in…') }}</span>
        </button>
    </form>
</div>
