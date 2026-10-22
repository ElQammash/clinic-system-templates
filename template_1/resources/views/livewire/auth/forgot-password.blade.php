<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink">{{ __('Forgot your password?') }}</h1>
    <p class="mt-2 text-sm text-muted">{{ __('Enter your e-mail and we will send you a link to choose a new password.') }}</p>

    @if ($status)
        <div class="mt-6 rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-800">{{ $status }}</div>
    @endif

    <form wire:submit="sendLink" class="mt-8 space-y-5">
        <x-field :label="__('E-mail')" for="email">
            <input wire:model="email" id="email" type="email" autofocus dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('email')])>
        </x-field>

        <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">{{ __('Send reset link') }}</button>
    </form>

    <a href="{{ route('login') }}" wire:navigate class="mt-6 inline-block text-sm font-medium text-primary-700 hover:underline">{{ __('Back to log in') }}</a>
</div>
