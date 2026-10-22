<div>
    <x-page-header :title="__('Settings')" :subtitle="__('Clinic information, branding and preferences. Changes apply immediately.')" />

    <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
        {{-- Tabs --}}
        <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                        @class(['nav-link shrink-0', 'active' => $tab === $key])>{{ $label }}</button>
            @endforeach
        </nav>

        <form wire:submit="save" class="card">
            <div class="space-y-6 p-6">
                @if ($errors->any())
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">{{ __('Please fix the following:') }}</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($tab === 'general')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field :label="__('Clinic name (Arabic)')" for="clinic_name" error="form.clinic_name" required>
                            <input wire:model="form.clinic_name" id="clinic_name" @class(['form-control', 'is-invalid' => $errors->has('form.clinic_name')])>
                        </x-field>
                        <x-field :label="__('Clinic name (English)')" for="clinic_name_en" error="form.clinic_name_en">
                            <input wire:model="form.clinic_name_en" id="clinic_name_en" dir="ltr" class="form-control text-start">
                        </x-field>
                        <x-field :label="__('Tagline (Arabic)')" for="clinic_tagline" error="form.clinic_tagline">
                            <input wire:model="form.clinic_tagline" id="clinic_tagline" class="form-control" placeholder="{{ __('e.g. Pediatrics & neonatology') }}">
                        </x-field>
                        <x-field :label="__('Tagline (English)')" for="clinic_tagline_en" error="form.clinic_tagline_en">
                            <input wire:model="form.clinic_tagline_en" id="clinic_tagline_en" dir="ltr" class="form-control text-start">
                        </x-field>
                        <x-field :label="__('Phone')" for="phone" error="form.phone">
                            <input wire:model="form.phone" id="phone" type="tel" dir="ltr" placeholder="01xxxxxxxxx" @class(['form-control text-start', 'is-invalid' => $errors->has('form.phone')])>
                        </x-field>
                        <x-field :label="__('Alternative phone')" for="phone_alt" error="form.phone_alt">
                            <input wire:model="form.phone_alt" id="phone_alt" type="tel" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.phone_alt')])>
                        </x-field>
                        <x-field :label="__('WhatsApp number')" for="whatsapp" error="form.whatsapp" :hint="__('Used as the sender number in appointment reminders.')">
                            <input wire:model="form.whatsapp" id="whatsapp" type="tel" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.whatsapp')])>
                        </x-field>
                        <x-field :label="__('E-mail')" for="email" error="form.email">
                            <input wire:model="form.email" id="email" type="email" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.email')])>
                        </x-field>
                        <x-field :label="__('Address (Arabic)')" for="address" error="form.address" class="sm:col-span-2">
                            <textarea wire:model="form.address" id="address" rows="2" class="form-control"></textarea>
                        </x-field>
                        <x-field :label="__('Address (English)')" for="address_en" error="form.address_en" class="sm:col-span-2">
                            <textarea wire:model="form.address_en" id="address_en" rows="2" dir="ltr" class="form-control text-start"></textarea>
                        </x-field>
                    </div>
                @endif

                @if ($tab === 'branding')
                    <div class="grid gap-8 md:grid-cols-2">
                        <div>
                            <p class="form-label">{{ __('Logo') }}</p>
                            <div class="flex items-center gap-4">
                                <div class="grid size-24 place-items-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50">
                                    @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                                        <img src="{{ $logo->temporaryUrl() }}" class="size-full object-contain" alt="">
                                    @elseif ($logoUrl && ! $removeLogo)
                                        <img src="{{ $logoUrl }}" class="size-full object-contain" alt="">
                                    @else
                                        <x-icon name="image" class="size-8 text-slate-300" />
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    <label class="btn btn-secondary btn-sm cursor-pointer">
                                        <x-icon name="image" class="size-4" /> {{ __('Choose image') }}
                                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="sr-only">
                                    </label>
                                    @if ($logoUrl && ! $removeLogo)
                                        <button type="button" wire:click="$set('removeLogo', true)" class="btn btn-ghost btn-sm text-rose-600">{{ __('Remove') }}</button>
                                    @endif
                                    <p class="form-hint">{{ __('PNG, JPG, WEBP or SVG, up to 1 MB. Square logos look best.') }}</p>
                                </div>
                            </div>
                            <div wire:loading wire:target="logo" class="form-hint">{{ __('Uploading…') }}</div>
                            @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-data="{ color: $wire.entangle('form.primary_color') }">
                            <p class="form-label">{{ __('Primary color') }}</p>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="color" :value="color || '#0d9488'" class="h-11 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
                                <input type="text" x-model="color" dir="ltr" placeholder="#0d9488" class="form-control w-32 text-start font-mono">
                                <button type="button" wire:click="resetColor" class="btn btn-ghost btn-sm">{{ __('Use template color') }}</button>
                            </div>
                            @error('form.primary_color') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="form-hint">{{ __('Leave empty to keep the template\'s own colors.') }}</p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach (['#0d9488', '#2563eb', '#7c3aed', '#db2777', '#ea580c', '#16a34a', '#0f172a'] as $swatch)
                                    <button type="button" x-on:click="color = '{{ $swatch }}'" class="size-8 rounded-full ring-2 ring-white ring-offset-1 ring-offset-slate-200 transition hover:scale-110" style="background: {{ $swatch }}" aria-label="{{ $swatch }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if ($tab === 'regional')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field :label="__('Default language')" for="default_locale" error="form.default_locale" :hint="__('Each user can still choose their own language.')">
                            <select wire:model="form.default_locale" id="default_locale" class="form-control">
                                @foreach (config('clinic.locales') as $code => $locale)
                                    <option value="{{ $code }}">{{ $locale['name'] }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field :label="__('Digits')" for="digits" error="form.digits">
                            <select wire:model="form.digits" id="digits" class="form-control">
                                <option value="western">{{ __('Western (0123456789)') }}</option>
                                <option value="arabic">{{ __('Arabic-Indic (٠١٢٣٤٥٦٧٨٩)') }}</option>
                            </select>
                        </x-field>
                        <x-field :label="__('Currency code')" for="currency_code" error="form.currency_code">
                            <input wire:model="form.currency_code" id="currency_code" dir="ltr" maxlength="3" class="form-control text-start uppercase">
                        </x-field>
                        <x-field :label="__('Currency symbol (Arabic)')" for="currency_symbol" error="form.currency_symbol">
                            <input wire:model="form.currency_symbol" id="currency_symbol" class="form-control">
                        </x-field>
                        <x-field :label="__('Currency symbol (English)')" for="currency_symbol_en" error="form.currency_symbol_en">
                            <input wire:model="form.currency_symbol_en" id="currency_symbol_en" dir="ltr" class="form-control text-start">
                        </x-field>
                        <x-field :label="__('Phone number format')" for="phone_format" error="form.phone_format">
                            <select wire:model="form.phone_format" id="phone_format" class="form-control">
                                <option value="egypt">{{ __('Egyptian mobile (01xxxxxxxxx)') }}</option>
                                <option value="any">{{ __('Any international number') }}</option>
                            </select>
                        </x-field>
                    </div>
                @endif

                @if ($tab === 'modules')
                    <x-field :label="__('Clinic type')" for="specialty" error="form.specialty" :hint="__('Used to suggest custom visit fields and default services.')">
                        <select wire:model="form.specialty" id="specialty" class="form-control sm:max-w-xs">
                            @foreach ($specialties as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-field>

                    <div>
                        <p class="form-label">{{ __('Optional modules') }}</p>
                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach ($modules as $module)
                                <label class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 transition has-checked:border-primary-500 has-checked:bg-primary-50/50">
                                    <input type="checkbox" wire:model="form.{{ $module->settingKey() }}" class="form-check mt-0.5">
                                    <span>
                                        <span class="block text-sm font-semibold text-ink">{{ $module->label() }}</span>
                                        <span class="mt-1 block text-xs leading-relaxed text-muted">{{ $module->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <label class="flex cursor-pointer gap-3 rounded-xl border border-line p-4 has-checked:border-primary-500 has-checked:bg-primary-50/50">
                        <input type="checkbox" wire:model="form.doctors_share_records" class="form-check mt-0.5">
                        <span>
                            <span class="block text-sm font-semibold text-ink">{{ __('Doctors share medical records') }}</span>
                            <span class="mt-1 block text-xs leading-relaxed text-muted">{{ __('When enabled, every doctor can read the full medical history of any patient. When disabled, doctors only see patients they have treated or booked.') }}</span>
                        </span>
                    </label>
                @endif

                @if ($tab === 'messages')
                    <x-field :label="__('WhatsApp appointment reminder')" for="reminder_template" error="form.reminder_template">
                        <textarea wire:model.live.debounce.500ms="form.reminder_template" id="reminder_template" rows="7" class="form-control leading-relaxed"></textarea>
                    </x-field>
                    <div>
                        <p class="form-label">{{ __('Placeholders') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($placeholders as $placeholder)
                                <button type="button" x-on:click="$wire.set('form.reminder_template', $wire.form.reminder_template + ' {{ $placeholder }}')" class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700 hover:bg-primary-50" dir="ltr">{{ $placeholder }}</button>
                            @endforeach
                        </div>
                        <p class="form-hint">{{ __('They are replaced with the patient, doctor and appointment details when the message is sent.') }}</p>
                    </div>
                @endif

                @if ($tab === 'printing')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field :label="__('Invoice footer (Arabic)')" for="invoice_footer" error="form.invoice_footer">
                            <textarea wire:model="form.invoice_footer" id="invoice_footer" rows="3" class="form-control"></textarea>
                        </x-field>
                        <x-field :label="__('Invoice footer (English)')" for="invoice_footer_en" error="form.invoice_footer_en">
                            <textarea wire:model="form.invoice_footer_en" id="invoice_footer_en" rows="3" dir="ltr" class="form-control text-start"></textarea>
                        </x-field>
                        <x-field :label="__('Prescription footer (Arabic)')" for="prescription_footer" error="form.prescription_footer">
                            <textarea wire:model="form.prescription_footer" id="prescription_footer" rows="3" class="form-control" placeholder="{{ __('e.g. Please bring this prescription on your next visit') }}"></textarea>
                        </x-field>
                        <x-field :label="__('Prescription footer (English)')" for="prescription_footer_en" error="form.prescription_footer_en">
                            <textarea wire:model="form.prescription_footer_en" id="prescription_footer_en" rows="3" dir="ltr" class="form-control text-start"></textarea>
                        </x-field>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="form.print_show_logo" class="form-check"> {{ __('Show the logo on printed documents') }}
                    </label>
                @endif
            </div>

            <div class="flex justify-end gap-2 rounded-b-(--radius-card) border-t border-line bg-slate-50/70 px-6 py-4">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,logo">
                    <x-icon name="check" class="size-4" /> {{ __('Save changes') }}
                </button>
            </div>
        </form>
    </div>
</div>
