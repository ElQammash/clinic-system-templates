@php use App\Enums\CustomFieldType; @endphp
<div>
    <x-modal wire:model="open" :title="$form->patient ? __('Edit patient') : __('New patient')" max-width="max-w-3xl">
        <form id="patient-form" wire:submit="save" class="space-y-6">
            {{-- Basic information --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Full name')" for="p-name" error="form.name" required class="sm:col-span-2">
                    <input wire:model="form.name" id="p-name" autocomplete="off" placeholder="{{ __('Patient full name (at least first and last name)') }}"
                           @class(['form-control text-base', 'is-invalid' => $errors->has('form.name')])>
                </x-field>

                <x-field :label="__('Phone')" for="p-phone" error="form.phone">
                    <input wire:model.live.debounce.500ms="form.phone" id="p-phone" type="tel" inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx"
                           @class(['form-control text-start', 'is-invalid' => $errors->has('form.phone')])>
                </x-field>
                <x-field :label="__('Alternative phone')" for="p-phone-alt" error="form.phone_alt">
                    <input wire:model="form.phone_alt" id="p-phone-alt" type="tel" inputmode="tel" dir="ltr"
                           @class(['form-control text-start', 'is-invalid' => $errors->has('form.phone_alt')])>
                </x-field>

                @if ($this->duplicates->isNotEmpty())
                    <div class="rounded-none border border-amber-200 bg-amber-50 p-4 text-sm sm:col-span-2">
                        <p class="font-semibold text-amber-900">{{ __('Patients with this phone number already exist:') }}</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($this->duplicates as $existing)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-amber-900">{{ $existing->name }} <span class="ltr-nums text-xs text-amber-700">#{{ $existing->file_number }}</span></span>
                                    <a href="{{ route('patients.show', $existing) }}" wire:navigate class="text-xs font-semibold text-amber-800 underline">{{ __('Open file') }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-2 text-xs text-amber-700">{{ __('You can still create a new file (e.g. for a family member).') }}</p>
                    </div>
                @endif

                <x-field :label="__('Gender')" error="form.gender">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($genders as $value => $label)
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-none border border-neutral-300 px-3 py-2.5 text-sm font-medium text-neutral-600 transition has-checked:border-primary-500 has-checked:bg-primary-50 has-checked:text-primary-800">
                                <input type="radio" wire:model="form.gender" value="{{ $value }}" class="sr-only"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </x-field>

                <div x-data="{ mode: $wire.entangle('form.birthMode') }">
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="form-label mb-0">{{ __('Birth date') }}</span>
                        <div class="flex rounded-none bg-neutral-100 p-0.5 text-xs font-medium">
                            <button type="button" x-on:click="mode = 'date'" :class="mode === 'date' ? 'bg-white shadow-none text-ink' : 'text-muted'" class="rounded-none px-2.5 py-1">{{ __('Date') }}</button>
                            <button type="button" x-on:click="mode = 'age'" :class="mode === 'age' ? 'bg-white shadow-none text-ink' : 'text-muted'" class="rounded-none px-2.5 py-1">{{ __('Age') }}</button>
                        </div>
                    </div>
                    <input x-show="mode === 'date'" wire:model="form.birth_date" type="date" max="{{ now()->format('Y-m-d') }}"
                           @class(['form-control', 'is-invalid' => $errors->has('form.birth_date')])>
                    <div x-show="mode === 'age'" x-cloak class="relative">
                        <input wire:model="form.age_years" type="number" min="0" max="120" inputmode="numeric" dir="ltr" placeholder="{{ __('Age in years') }}"
                               @class(['form-control pe-14 text-start', 'is-invalid' => $errors->has('form.age_years')])>
                        <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-xs text-muted">{{ __('years') }}</span>
                    </div>
                    @error('form.birth_date') <p class="form-error">{{ $message }}</p> @enderror
                    @error('form.age_years') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <x-field :label="__('National ID')" for="p-nid" error="form.national_id">
                    <input wire:model="form.national_id" id="p-nid" inputmode="numeric" dir="ltr" maxlength="20"
                           @class(['form-control text-start font-mono', 'is-invalid' => $errors->has('form.national_id')])>
                </x-field>
                <x-field :label="__('Occupation')" for="p-occupation" error="form.occupation">
                    <input wire:model="form.occupation" id="p-occupation" class="form-control">
                </x-field>
                <x-field :label="__('Address')" for="p-address" error="form.address" class="sm:col-span-2">
                    <input wire:model="form.address" id="p-address" class="form-control">
                </x-field>
            </div>

            {{-- Medical background: only for users with medical access --}}
            @if ($canSeeMedical)
                <fieldset class="rounded-none border border-rose-100 bg-rose-50/40 p-4">
                    <legend class="px-2 text-sm font-semibold text-rose-800">{{ __('Medical background') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field :label="__('Blood type')" for="p-blood" error="form.blood_type">
                            <select wire:model="form.blood_type" id="p-blood" class="form-control">
                                <option value="">{{ __('Unknown') }}</option>
                                @foreach ($bloodTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field :label="__('Allergies')" for="p-allergies" error="form.allergies" class="sm:col-span-2" :hint="__('Separate with commas, e.g. penicillin, aspirin')">
                            <input wire:model="form.allergies" id="p-allergies" class="form-control">
                        </x-field>
                        <x-field :label="__('Chronic diseases')" for="p-chronic" error="form.chronic_diseases" class="sm:col-span-3">
                            <input wire:model="form.chronic_diseases" id="p-chronic" class="form-control" placeholder="{{ __('e.g. diabetes, hypertension') }}">
                        </x-field>
                    </div>
                </fieldset>
            @endif

            @if ($customFields->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($customFields as $field)
                        @include('livewire.partials.custom-field-input', ['field' => $field, 'model' => 'form.custom'])
                    @endforeach
                </div>
            @endif

            <x-field :label="__('Notes')" for="p-notes" error="form.notes">
                <textarea wire:model="form.notes" id="p-notes" rows="2" class="form-control" placeholder="{{ __('Any administrative notes (not medical)') }}"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="patient-form" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <x-icon name="check" class="size-4" /> {{ $form->patient ? __('Save') : __('Create patient file') }}
            </button>
        </x-slot:footer>
    </x-modal>
</div>
