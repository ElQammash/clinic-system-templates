@php use App\Support\Format; @endphp
<div>
    <x-modal wire:model="open" :title="$appointmentId ? __('Reschedule appointment') : __('Book appointment')" max-width="max-w-2xl">
        <form id="booking-form" wire:submit="save" class="space-y-5">
            {{-- Patient --}}
            <div>
                <p class="form-label">{{ __('Patient') }} <span class="text-rose-500">*</span></p>
                @if ($this->patient)
                    <div class="flex items-center gap-3 rounded-none border border-primary-200 bg-primary-50/50 px-4 py-3">
                        <span class="grid size-10 place-items-center rounded-full bg-white text-sm font-semibold text-primary-700">{{ $this->patient->initials() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ $this->patient->name }}</p>
                            <p class="text-xs text-muted"><span class="ltr-nums">#{{ $this->patient->file_number }}</span> · <span class="ltr-nums">{{ $this->patient->phone }}</span> @if ($this->patient->age()) · {{ $this->patient->age() }} @endif</p>
                        </div>
                        @unless ($appointmentId)
                            <button type="button" wire:click="$set('patientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                        @endunless
                    </div>
                @else
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-neutral-400" />
                        <input wire:model.live.debounce.300ms="patientSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}"
                               @class(['form-control ps-10', 'is-invalid' => $errors->has('patientId')])>
                    </div>
                    @if (mb_strlen($patientSearch) >= 2)
                        <ul class="mt-2 max-h-56 divide-y divide-line overflow-y-auto rounded-none border border-line">
                            @foreach ($this->patientResults as $result)
                                <li>
                                    <button type="button" wire:click="selectPatient({{ $result->id }})" class="flex w-full items-center gap-3 px-4 py-2.5 text-start hover:bg-primary-50/60">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium">{{ $result->name }}</span>
                                            <span class="text-xs text-muted"><span class="ltr-nums">#{{ $result->file_number }}</span> · <span class="ltr-nums">{{ $result->phone }}</span></span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                            @can('create', App\Models\Patient::class)
                                <li>
                                    <button type="button" wire:click="newPatient" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-50/60">
                                        <x-icon name="user-plus" class="size-4" /> {{ __('New patient') }}: “{{ $patientSearch }}”
                                    </button>
                                </li>
                            @endcan
                        </ul>
                    @endif
                    @error('patientId') <p class="form-error">{{ $message }}</p> @enderror
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Doctor')" for="b-doctor" error="doctorId" required>
                    <select wire:model.live="doctorId" id="b-doctor" class="form-control">
                        @foreach ($this->doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->displayName() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('Visit type')" for="b-type" error="visitTypeId">
                    <select wire:model.live="visitTypeId" id="b-type" class="form-control">
                        @foreach ($visitTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name() }} — {{ money($type->default_price) }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('Date')" for="b-date" error="date" required>
                    <input wire:model.live="date" id="b-date" type="date" min="{{ today()->toDateString() }}" class="form-control">
                </x-field>
                <x-field :label="__('Booked via')" for="b-source" error="source">
                    <select wire:model="source" id="b-source" class="form-control">
                        @foreach ($sources as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            {{-- Free slots --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <p class="form-label mb-0">{{ __('Time') }} <span class="text-rose-500">*</span></p>
                    <span class="text-xs text-muted" wire:loading wire:target="doctorId,date,visitTypeId">{{ __('Loading…') }}</span>
                </div>
                @if ($this->slots)
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                        @foreach ($this->slots as $slot)
                            <button type="button" wire:click="$set('time', '{{ $slot->format('H:i') }}')" @class([
                                'rounded-none border px-2 py-2 text-sm font-semibold transition ltr-nums',
                                'border-primary-600 bg-primary-600 text-white shadow-none' => $time === $slot->format('H:i'),
                                'border-neutral-200 bg-white text-neutral-700 hover:border-primary-400 hover:text-primary-700' => $time !== $slot->format('H:i'),
                            ])>{{ Format::time($slot) }}</button>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-none bg-neutral-50 px-4 py-3 text-sm text-muted">{{ __('No free slots on this day. Try another date or doctor.') }}</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                    <label for="b-time" class="text-muted">{{ __('Or enter a time:') }}</label>
                    <input wire:model.live="time" id="b-time" type="time" step="300" class="form-control w-36 py-1.5">
                    @if ($canOverrideHours)
                        <label class="flex items-center gap-2 text-neutral-600"><input type="checkbox" wire:model="outsideHours" class="form-check"> {{ __('Allow outside working hours') }}</label>
                    @endif
                </div>
                @error('time') <p class="form-error">{{ $message }}</p> @enderror
                @error('starts_at') <div class="mt-3 rounded-none bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div> @enderror
                @error('doctor_id') <div class="mt-3 rounded-none bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div> @enderror
                @error('patient_id') <div class="mt-3 rounded-none bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div> @enderror
            </div>

            <x-field :label="__('Notes')" for="b-notes" error="notes">
                <input wire:model="notes" id="b-notes" class="form-control" placeholder="{{ __('e.g. first visit, bring previous X-rays') }}">
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="booking-form" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <x-icon name="check" class="size-4" /> {{ $appointmentId ? __('Save new time') : __('Confirm booking') }}
            </button>
        </x-slot:footer>
    </x-modal>
</div>
