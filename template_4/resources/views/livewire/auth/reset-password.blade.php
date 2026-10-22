<div>
    <h1 class="font-serif text-4xl text-ink">{{ __('Choose a new password') }}</h1>

    <form wire:submit="resetPassword" class="mt-8 space-y-5">
        <x-field :label="__('E-mail')" for="email">
            <input wire:model="email" id="email" type="email" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('email')])>
        </x-field>
        <x-field :label="__('New password')" for="password">
            <input wire:model="password" id="password" type="password" autocomplete="new-password" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('password')])>
        </x-field>
        <x-field :label="__('Confirm password')" for="password_confirmation">
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" dir="ltr" class="form-control text-start">
        </x-field>

        <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">{{ __('Reset password') }}</button>
    </form>
</div>
