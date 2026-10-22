<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink">{{ __('Welcome back') }}</h1>
    <p class="mt-2 text-sm text-muted">{{ __('Log in to manage your clinic.') }}</p>

    @if (session('status'))
        <div class="mt-6 rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-800">{{ session('status') }}</div>
    @endif

    <form wire:submit="login" class="mt-8 space-y-5">
        <x-field :label="__('E-mail')" for="email">
            <input wire:model="email" id="email" type="email" autocomplete="username" autofocus dir="ltr"
                   @class(['form-control text-start', 'is-invalid' => $errors->has('email')])>
        </x-field>

        <x-field for="password">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="form-label mb-0">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-medium text-primary-700 hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <div x-data="{ show: false }" class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" dir="ltr"
                       @class(['form-control pe-10 text-start', 'is-invalid' => $errors->has('password')])>
                <button type="button" x-on:click="show = !show" class="absolute inset-y-0 end-0 grid w-10 place-items-center text-slate-400 hover:text-slate-600" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                    <x-heroicon-o-eye class="size-5" x-show="!show" />
                    <x-heroicon-o-eye-slash class="size-5" x-show="show" x-cloak />
                </button>
            </div>
        </x-field>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input wire:model="remember" type="checkbox" class="form-check"> {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
            <span wire:loading wire:target="login">{{ __('Logging in…') }}</span>
        </button>
    </form>
</div>
