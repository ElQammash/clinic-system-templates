@php use App\Support\Format; @endphp
<div>
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-sm text-muted">
        <a href="{{ route('patients.index') }}" wire:navigate class="hover:text-primary-700">{{ __('Patients') }}</a>
        <span>/</span>
        <span class="text-ink">{{ $patient->name }}</span>
    </nav>

    @if ($patient->trashed())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ __('This patient file was deleted on :date. It is read-only until restored.', ['date' => Format::date($patient->deleted_at)]) }}
        </div>
    @endif

    {{-- Header card --}}
    <div class="card overflow-hidden">
        <div class="h-20 bg-gradient-to-l from-primary-500/90 via-primary-600 to-secondary-600 sm:h-24"></div>
        <div class="px-5 pb-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <span @class([
                    '-mt-10 grid size-20 shrink-0 place-items-center rounded-2xl text-2xl font-bold ring-4 ring-white shadow-sm sm:-mt-12 sm:size-24',
                    'bg-sky-100 text-sky-700' => $patient->gender?->value === 'male',
                    'bg-pink-100 text-pink-700' => $patient->gender?->value === 'female',
                    'bg-slate-100 text-slate-600' => ! $patient->gender,
                ])>{{ $patient->initials() }}</span>

                <div class="min-w-0 flex-1 sm:pt-4">
                    <h1 class="text-xl font-bold text-ink sm:text-2xl">{{ $patient->name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <x-badge color="slate"><span class="ltr-nums font-mono">#{{ $patient->file_number }}</span></x-badge>
                        @if ($patient->age())
                            <x-badge color="primary">{{ $patient->age() }}@if ($patient->birth_date_is_estimated) ~@endif</x-badge>
                        @endif
                        @if ($patient->gender)
                            <x-badge :color="$patient->gender->value === 'male' ? 'sky' : 'rose'">{{ $patient->gender->label() }}</x-badge>
                        @endif
                        @if ($balance > 0)
                            <x-badge color="rose">{{ __('Owes :amount', ['amount' => money($balance)]) }}</x-badge>
                        @endif
                        @if ($canSeeMedical && $patient->blood_type)
                            <x-badge color="rose"><x-icon name="blood" class="size-3.5" solid /> {{ $patient->blood_type->value }}</x-badge>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 sm:pt-4">
                    @if ($patient->phone)
                        <a href="tel:{{ $patient->phone }}" class="btn btn-secondary"><x-icon name="phone" class="size-4" /> <span class="ltr-nums">{{ $patient->phone }}</span></a>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-secondary text-emerald-700" title="WhatsApp"><x-icon name="whatsapp" class="size-4" /></a>
                    @endif
                    @can('update', $patient)
                        <button type="button" x-on:click="$dispatch('edit-patient', { id: {{ $patient->id }} })" class="btn btn-primary"><x-icon name="edit" class="size-4" /> {{ __('Edit') }}</button>
                    @endcan
                    @can('delete', $patient)
                        @unless ($patient->trashed())
                            <button type="button" wire:click="delete" wire:confirm="{{ __('Delete this patient file? It can be restored later by an admin.') }}" class="btn btn-ghost text-rose-600" title="{{ __('Delete') }}"><x-icon name="trash" class="size-5" /></button>
                        @endunless
                    @endcan
                </div>
            </div>

            {{-- Safety alerts --}}
            @if ($canSeeMedical && ($patient->allergyList() || $patient->chronicDiseaseList()))
                <div class="mt-5 flex flex-wrap items-center gap-2 rounded-xl bg-rose-50/70 px-4 py-3">
                    @foreach ($patient->allergyList() as $allergy)
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white"><x-icon name="allergy" class="size-3.5" /> {{ __('Allergy') }}: {{ $allergy }}</span>
                    @endforeach
                    @foreach ($patient->chronicDiseaseList() as $disease)
                        <x-badge color="amber">{{ $disease }}</x-badge>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 overflow-x-auto border-t border-line px-3 sm:px-4">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                    'relative shrink-0 px-4 py-3.5 text-sm font-semibold transition',
                    'text-primary-700 after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:rounded-full after:bg-primary-600' => $tab === $key,
                    'text-muted hover:text-ink' => $tab !== $key,
                ])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-6">
        @if ($tab === 'overview')
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="card p-6 lg:col-span-2">
                    <h2 class="mb-4 font-semibold text-ink">{{ __('Personal information') }}</h2>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ([
                            __('Phone') => $patient->phone,
                            __('Alternative phone') => $patient->phone_alt,
                            __('Birth date') => $patient->birth_date && ! $patient->birth_date_is_estimated ? Format::date($patient->birth_date) : null,
                            __('National ID') => $patient->national_id,
                            __('Occupation') => $patient->occupation,
                            __('Address') => $patient->address,
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs font-medium text-muted">{{ $label }}</dt>
                                <dd class="mt-1 text-sm text-ink"><span @class(['ltr-nums' => in_array($label, [__('Phone'), __('Alternative phone'), __('National ID')])])>{{ $value ?: '—' }}</span></dd>
                            </div>
                        @endforeach
                        @foreach ($customValues as $custom)
                            <div>
                                <dt class="text-xs font-medium text-muted">{{ $custom['label'] }}</dt>
                                <dd class="mt-1 text-sm text-ink">{{ $custom['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($patient->notes)
                        <div class="mt-6 rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-medium text-muted">{{ __('Notes') }}</p>
                            <p class="mt-1 text-sm whitespace-pre-line text-ink">{{ $patient->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    @if ($canSeeMedical)
                        <div class="card p-6">
                            <h2 class="mb-4 font-semibold text-ink">{{ __('Medical background') }}</h2>
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="text-xs font-medium text-muted">{{ __('Blood type') }}</dt>
                                    <dd class="mt-1 font-semibold">{{ $patient->blood_type?->value ?? __('Unknown') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted">{{ __('Allergies') }}</dt>
                                    <dd class="mt-1">{{ $patient->allergies ?: __('No known allergies') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-muted">{{ __('Chronic diseases') }}</dt>
                                    <dd class="mt-1">{{ $patient->chronic_diseases ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    <div class="card p-6 text-sm">
                        <h2 class="mb-4 font-semibold text-ink">{{ __('File') }}</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Opened on') }}</dt><dd>{{ Format::date($patient->created_at) }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Registered by') }}</dt><dd>{{ $patient->creator?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Attachments') }}</dt><dd>{{ Format::number($patient->attachments()->count()) }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        @endif

        @if ($tab === 'timeline')
            <div class="card p-6">
                <ol class="relative space-y-6 border-s-2 border-primary-100 ps-6">
                    @foreach ($timeline as $entry)
                        <li class="relative">
                            <span class="absolute -start-[2.15rem] top-0 grid size-8 place-items-center rounded-full bg-white ring-2 ring-primary-100">
                                <x-icon :name="$entry->icon" class="size-4 text-primary-600" />
                            </span>
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="font-semibold text-ink">
                                    @if ($entry->url)<a href="{{ $entry->url }}" wire:navigate class="hover:text-primary-700">{{ $entry->title }}</a>@else{{ $entry->title }}@endif
                                </p>
                                <time class="text-xs text-muted">{{ Format::dateTime($entry->date) }}</time>
                            </div>
                            @if ($entry->subtitle || $entry->amount)
                                <p class="mt-1 text-sm text-muted">{{ $entry->subtitle }} @if ($entry->amount)<span class="font-semibold text-ink">· {{ $entry->amount }}</span>@endif</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($tab === 'visits')
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-semibold text-ink">{{ __('Visits') }}</h2>
                    @if ($canStartVisit)
                        <form method="post" action="{{ route('visits.start') }}">
                            @csrf
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                            <button type="submit" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> {{ __('New visit') }}</button>
                        </form>
                    @endif
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($visits as $visit)
                        <li class="flex flex-wrap items-start gap-4 px-5 py-4" wire:key="pv-{{ $visit->id }}">
                            <div class="w-32 shrink-0">
                                <p class="text-sm font-semibold text-ink">{{ Format::date($visit->visited_at) }}</p>
                                <p class="text-xs text-muted">{{ $visit->doctor->displayName() }}</p>
                            </div>
                            <div class="min-w-0 flex-1 text-sm">
                                <a href="{{ route('visits.show', $visit) }}" wire:navigate class="font-semibold text-ink hover:text-primary-700">{{ $visit->diagnosis ?: ($visit->chief_complaint ?: __('Visit')) }}</a>
                                @if ($visit->prescription)
                                    <p class="mt-1 text-xs text-muted"><span class="font-bold text-primary-600">℞</span> {{ $visit->prescription->items->pluck('medicine_name')->implode('، ') }}</p>
                                @endif
                                @if ($visit->bloodPressure() || $visit->weight)
                                    <p class="mt-1 text-xs text-muted ltr-nums">@if ($visit->bloodPressure()) BP {{ $visit->bloodPressure() }} @endif @if ($visit->weight) · {{ Format::number($visit->weight, 1) }} kg @endif</p>
                                @endif
                            </div>
                            @if ($visit->prescription)
                                <a href="{{ route('print.prescription', $visit->prescription) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="print" class="size-4" /></a>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-12 text-center text-sm text-muted">{{ __('No visits yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($tab === 'billing')
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-ink">{{ __('Billing') }}</h2>
                        <p class="text-sm {{ $balance > 0 ? 'font-semibold text-rose-600' : 'text-muted' }}">{{ __('Balance due: :amount', ['amount' => money($balance)]) }}</p>
                    </div>
                    @can('create', App\Models\Invoice::class)
                        <a href="{{ route('invoices.create', ['patient' => $patient->id]) }}" wire:navigate class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> {{ __('New invoice') }}</a>
                    @endcan
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($invoices as $invoice)
                        <li wire:key="pi-{{ $invoice->id }}">
                            <a href="{{ route('invoices.show', $invoice) }}" wire:navigate class="flex flex-wrap items-center gap-4 px-5 py-3.5 transition hover:bg-primary-50/40">
                                <span class="w-36 shrink-0 font-mono text-xs font-semibold" dir="ltr">{{ $invoice->number }}</span>
                                <span class="flex-1 text-sm text-muted">{{ Format::date($invoice->issued_at) }}</span>
                                <span class="text-sm font-semibold">{{ money($invoice->total) }}</span>
                                @if ($invoice->balance > 0)<span class="text-xs font-semibold text-rose-600">{{ __('Remaining') }}: {{ money($invoice->balance) }}</span>@endif
                                <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-12 text-center text-sm text-muted">{{ __('No invoices yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($tab === 'appointments')
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-semibold text-ink">{{ __('Appointments') }}</h2>
                    @can('create', App\Models\Appointment::class)
                        @unless ($patient->trashed())
                            <button type="button" x-on:click="$dispatch('book-appointment', { patientId: {{ $patient->id }} })" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> {{ __('Book appointment') }}</button>
                        @endunless
                    @endcan
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($appointments as $appointment)
                        <li wire:key="pa-{{ $appointment->id }}">
                            <button type="button" x-on:click="$dispatch('show-appointment', { id: {{ $appointment->id }} })" class="flex w-full flex-wrap items-center gap-4 px-5 py-3.5 text-start transition hover:bg-primary-50/40">
                                <div class="w-36 shrink-0">
                                    <p class="text-sm font-semibold text-ink">{{ Format::date($appointment->starts_at, 'D j M Y') }}</p>
                                    <p class="text-xs text-muted ltr-nums">{{ Format::time($appointment->starts_at) }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $appointment->visitType?->name() ?? __('Appointment') }}</p>
                                    <p class="truncate text-xs text-muted">{{ $appointment->doctor->displayName() }}</p>
                                </div>
                                <x-badge :color="$appointment->status->color()">{{ $appointment->status->label() }}</x-badge>
                            </button>
                        </li>
                    @empty
                        <li class="px-5 py-12 text-center text-sm text-muted">{{ __('No appointments yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        @endif

        @if ($tab === 'attachments')
            <livewire:patients.attachment-manager :patient="$patient" :key="'attachments-'.$patient->id" />
        @endif
    </div>

    <livewire:patients.patient-editor :redirect-on-create="false" />
    @can('viewAny', App\Models\Appointment::class)
        <livewire:appointments.booking-form />
        <livewire:appointments.appointment-details />
    @endcan
</div>
