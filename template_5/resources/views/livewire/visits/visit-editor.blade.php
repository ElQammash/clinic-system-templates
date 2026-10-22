@php use App\Models\Visit; use App\Support\Format; @endphp
<div>
    <nav class="mb-4 flex items-center gap-2 text-sm text-muted">
        <a href="{{ route('patients.show', $patient) }}" wire:navigate class="hover:text-primary-700">{{ $patient->name }}</a>
        <span>/</span>
        <span class="text-ink">{{ __('Visit') }} · {{ Format::dateTime($visit->visited_at) }}</span>
    </nav>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-ink">{{ __('Visit') }}</h1>
            @if ($visit->isCompleted())
                <x-badge color="emerald">{{ __('Completed') }}</x-badge>
            @else
                <x-badge color="violet">{{ __('In progress') }}</x-badge>
            @endif
            <span class="text-sm text-muted">{{ $visit->doctor->displayName() }}</span>
        </div>
        @if ($canEdit || auth()->user()->can('create', App\Models\Invoice::class))
            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\Invoice::class)
                    <a href="{{ route('invoices.create', ['visit' => $visit->id]) }}" wire:navigate class="btn btn-ghost"><x-icon name="receipt" class="size-4" /> {{ __('Invoice') }}</a>
                @endcan
            </div>
        @endif
        @if ($canEdit)
            <div class="flex flex-wrap gap-2">
                <span wire:dirty wire:target="form" class="self-center text-sm font-medium text-amber-600">{{ __('Unsaved changes') }}</span>
                <button type="button" wire:click="save" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="save"><x-icon name="check" class="size-4" /> {{ __('Save') }}</button>
                @unless ($visit->isCompleted())
                    <button type="button" wire:click="finish" class="btn btn-primary" wire:loading.attr="disabled"><x-icon name="success" class="size-4" /> {{ __('Save & finish visit') }}</button>
                @endunless
            </div>
        @endif
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <fieldset @disabled(! $canEdit) class="space-y-6">
                {{-- Vitals --}}
                <section class="card p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-semibold text-ink">{{ __('Vital signs') }}</h2>
                        @if ($bmi)
                            @php $category = Visit::bmiCategory($bmi); @endphp
                            <x-badge :color="$category['color']">BMI {{ Format::number($bmi, 1) }} · {{ $category['label'] }}</x-badge>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                            <label class="form-label">{{ __('Blood pressure') }} <span class="text-sm text-muted">mmHg</span></label>
                            <div class="flex items-center gap-1" dir="ltr">
                                <input wire:model="form.bp_systolic" inputmode="numeric" placeholder="120" @class(['form-control text-center', 'is-invalid' => $errors->has('form.bp_systolic')])>
                                <span class="text-lg text-slate-600">/</span>
                                <input wire:model="form.bp_diastolic" inputmode="numeric" placeholder="80" @class(['form-control text-center', 'is-invalid' => $errors->has('form.bp_diastolic')])>
                            </div>
                        </div>
                        @foreach ([
                            'pulse' => [__('Pulse'), 'bpm', '80'],
                            'temperature' => [__('Temperature'), '°C', '37'],
                            'spo2' => [__('SpO₂'), '%', '98'],
                        ] as $key => [$label, $unit, $placeholder])
                            <div>
                                <label class="form-label">{{ $label }} <span class="text-sm text-muted">{{ $unit }}</span></label>
                                <input wire:model="form.{{ $key }}" inputmode="decimal" dir="ltr" placeholder="{{ $placeholder }}" @class(['form-control text-center', 'is-invalid' => $errors->has('form.'.$key)])>
                            </div>
                        @endforeach
                        <div>
                            <label class="form-label">{{ __('Weight') }} <span class="text-sm text-muted">kg</span></label>
                            <input wire:model.live.debounce.400ms="form.weight" inputmode="decimal" dir="ltr" @class(['form-control text-center', 'is-invalid' => $errors->has('form.weight')])>
                        </div>
                        <div>
                            <label class="form-label">{{ __('Height') }} <span class="text-sm text-muted">cm</span></label>
                            <input wire:model.live.debounce.400ms="form.height" inputmode="decimal" dir="ltr" @class(['form-control text-center', 'is-invalid' => $errors->has('form.height')])>
                        </div>
                    </div>
                </section>

                {{-- Clinical notes --}}
                <section class="card space-y-4 p-5">
                    <h2 class="font-semibold text-ink">{{ __('Clinical notes') }}</h2>
                    <x-field :label="__('Chief complaint')" for="v-complaint" error="form.chief_complaint">
                        <textarea wire:model="form.chief_complaint" id="v-complaint" rows="2" class="form-control" placeholder="{{ __('Why did the patient come today?') }}"></textarea>
                    </x-field>
                    <x-field :label="__('Examination')" for="v-exam" error="form.examination">
                        <textarea wire:model="form.examination" id="v-exam" rows="3" class="form-control"></textarea>
                    </x-field>
                    <x-field :label="__('Diagnosis')" for="v-diagnosis" error="form.diagnosis">
                        <textarea wire:model="form.diagnosis" id="v-diagnosis" rows="2" class="form-control font-medium"></textarea>
                    </x-field>
                    <x-field :label="__('Doctor\'s notes')" for="v-notes" error="form.notes" :hint="__('Private medical notes, never printed.')">
                        <textarea wire:model="form.notes" id="v-notes" rows="2" class="form-control"></textarea>
                    </x-field>
                </section>

                @if ($customFields->isNotEmpty())
                    <section class="card p-5">
                        <h2 class="mb-4 font-semibold text-ink">{{ __('Additional information') }}</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($customFields as $field)
                                @include('livewire.partials.custom-field-input', ['field' => $field, 'model' => 'form.custom'])
                            @endforeach
                        </div>
                    </section>
                @endif
            </fieldset>

            {{-- Prescription --}}
            <livewire:visits.prescription-builder :visit="$visit" :key="'rx-'.$visit->id" />

            {{-- Lab & imaging --}}
            @if ($labEnabled)
                <livewire:visits.lab-requests :visit="$visit" :key="'lab-'.$visit->id" />
            @endif

            {{-- Attachments for this visit --}}
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-ink">{{ __('Attachments') }}</h2>
                <livewire:patients.attachment-manager :patient="$patient" :visit-id="$visit->id" :key="'att-visit-'.$visit->id" />
            </section>

            {{-- Follow-up --}}
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-ink">{{ __('Follow-up') }}</h2>
                @if ($followUp)
                    <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-primary-50 px-4 py-3 text-sm">
                        <x-icon name="calendar" class="size-5 text-primary-700" />
                        <span class="font-semibold text-primary-900">{{ __('Follow-up booked on :date at :time', ['date' => Format::longDate($followUp->starts_at), 'time' => Format::time($followUp->starts_at)]) }}</span>
                        <x-badge :color="$followUp->status->color()">{{ $followUp->status->label() }}</x-badge>
                    </div>
                @else
                    <div class="flex flex-wrap items-end gap-3">
                        <x-field :label="__('Follow-up date')" for="v-follow" error="form.follow_up_date">
                            <input wire:model="form.follow_up_date" id="v-follow" type="date" min="{{ today()->addDay()->toDateString() }}" class="form-control w-48" @disabled(! $canEdit)>
                        </x-field>
                        <div class="flex gap-1 pb-0.5">
                            @foreach ([7 => __('1 week'), 14 => __('2 weeks'), 30 => __('1 month')] as $days => $label)
                                <button type="button" wire:click="$set('form.follow_up_date', '{{ today()->addDays($days)->toDateString() }}')" class="btn btn-ghost btn-sm" @disabled(! $canEdit)>{{ $label }}</button>
                            @endforeach
                        </div>
                        @if ($canEdit && $form->follow_up_date && auth()->user()->can('create', App\Models\Appointment::class))
                            <button type="button" wire:click="bookFollowUp" class="btn btn-primary"><x-icon name="calendar" class="size-4" /> {{ __('Book the follow-up appointment') }}</button>
                        @endif
                    </div>
                @endif
            </section>
        </div>

        {{-- Patient summary --}}
        <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
            <div class="card p-5">
                <div class="flex items-center gap-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-primary-50 text-lg font-bold text-primary-700">{{ $patient->initials() }}</span>
                    <div class="min-w-0">
                        <a href="{{ route('patients.show', $patient) }}" wire:navigate class="block truncate font-semibold text-ink hover:text-primary-700">{{ $patient->name }}</a>
                        <p class="text-sm text-muted"><span class="ltr-nums">#{{ $patient->file_number }}</span> · {{ $patient->age() }} · {{ $patient->gender?->label() }}</p>
                    </div>
                </div>

                @if ($patient->allergyList())
                    <div class="mt-4 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white">
                        <x-icon name="allergy" class="inline size-4" /> {{ __('Allergies') }}: {{ implode('، ', $patient->allergyList()) }}
                    </div>
                @endif

                <dl class="mt-4 space-y-2 text-sm">
                    @if ($patient->chronicDiseaseList())
                        <div><dt class="text-sm text-muted">{{ __('Chronic diseases') }}</dt><dd class="font-medium">{{ implode('، ', $patient->chronicDiseaseList()) }}</dd></div>
                    @endif
                    @if ($patient->blood_type)
                        <div class="flex justify-between"><dt class="text-muted">{{ __('Blood type') }}</dt><dd class="font-semibold">{{ $patient->blood_type->value }}</dd></div>
                    @endif
                    @if ($lastVitals)
                        <div class="flex justify-between"><dt class="text-muted">{{ __('Last weight') }}</dt><dd class="font-semibold ltr-nums">{{ Format::number($lastVitals->weight, 1) }} kg <span class="text-sm font-normal text-muted">({{ Format::date($lastVitals->visited_at, 'j M') }})</span></dd></div>
                    @endif
                </dl>
            </div>

            <div class="card overflow-hidden">
                <h3 class="border-b border-line px-5 py-3 text-sm font-semibold text-ink">{{ __('Previous visits') }}</h3>
                <ul class="divide-y divide-line">
                    @forelse ($previousVisits as $previous)
                        <li>
                            <a href="{{ route('visits.show', $previous) }}" wire:navigate class="block px-5 py-3 text-sm transition hover:bg-primary-50/40">
                                <span class="text-sm text-muted">{{ Format::date($previous->visited_at) }} · {{ $previous->doctor->displayName() }}</span>
                                <span class="mt-0.5 block truncate font-medium text-ink">{{ $previous->diagnosis ?: ($previous->chief_complaint ?: '—') }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">{{ __('First visit') }}</li>
                    @endforelse
                </ul>
            </div>
        </aside>
    </div>

    <livewire:appointments.booking-form />
    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
