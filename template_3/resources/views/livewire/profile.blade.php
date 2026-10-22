<div>
    <x-page-header emoji="id" :title="__('My profile')" :subtitle="__('Your personal details, language and password.')" />

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="saveProfile" class="card p-6">
            <h2 class="mb-5 font-semibold text-ink">{{ __('Personal details') }}</h2>
            <div class="space-y-4">
                <x-field :label="__('Name')" for="name" required>
                    <input wire:model="name" id="name" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                </x-field>
                <x-field :label="__('Phone')" for="phone">
                    <input wire:model="phone" id="phone" type="tel" dir="ltr" placeholder="01xxxxxxxxx" @class(['form-control text-start', 'is-invalid' => $errors->has('phone')])>
                </x-field>
                <x-field :label="__('Language')" for="locale">
                    <select wire:model="locale" id="locale" class="form-control">
                        @foreach (config('clinic.locales') as $code => $locale)
                            <option value="{{ $code }}">{{ $locale['name'] }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">{{ __('Save') }}</button>
            </div>
        </form>

        <form wire:submit="changePassword" class="card p-6">
            <h2 class="mb-5 font-semibold text-ink">{{ __('Change password') }}</h2>
            <div class="space-y-4">
                <x-field :label="__('Current password')" for="current_password">
                    <input wire:model="current_password" id="current_password" type="password" autocomplete="current-password" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('current_password')])>
                </x-field>
                <x-field :label="__('New password')" for="password" :hint="__('At least 8 characters with letters and numbers.')">
                    <input wire:model="password" id="password" type="password" autocomplete="new-password" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('password')])>
                </x-field>
                <x-field :label="__('Confirm password')" for="password_confirmation">
                    <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" dir="ltr" class="form-control text-start">
                </x-field>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="changePassword">{{ __('Change password') }}</button>
            </div>
        </form>
    </div>
</div>
