@php use App\Support\Format; @endphp
{{-- Tablet-First patient file: split screen — summary + big vertical tabs on the start side, content on the end side. --}}
<div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <a href="{{ route('patients.index') }}" wire:navigate class="btn btn-secondary w-full justify-start"><x-icon name="chevron-right" class="size-6 ltr:rotate-180" /> {{ __('Patients') }}</a>

        <section class="card p-5">
            <div class="flex items-center gap-4">
                <span @class([
                    'grid size-16 shrink-0 place-items-center rounded-2xl text-2xl font-extrabold',
                    'bg-sky-600 text-white' => $patient->gender?->value === 'male',
                    'bg-pink-600 text-white' => $patient->gender?->value === 'female',
                    'bg-slate-200 text-ink' => ! $patient->gender,
                ])>{{ $patient->initials() }}</span>
                <div class="min-w-0">
                    <h1 class="text-2xl leading-tight font-extrabold">{{ $patient->name }}</h1>
                    <p class="text-lg font-bold text-muted ltr-nums">#{{ $patient->file_number }}</p>
                </div>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-2 text-center">
                <div class="rounded-2xl bg-slate-100 p-3"><dt class="text-sm font-bold text-muted">{{ __('Age') }}</dt><dd class="text-lg font-extrabold">{{ $patient->age() ?: '—' }}</dd></div>
                @if ($canSeeMedical)
                    <div class="rounded-2xl bg-slate-100 p-3"><dt class="text-sm font-bold text-muted">{{ __('Blood type') }}</dt><dd class="text-lg font-extrabold text-rose-700" dir="ltr">{{ $patient->blood_type?->value ?? '—' }}</dd></div>
                @else
                    <div class="rounded-2xl bg-slate-100 p-3"><dt class="text-sm font-bold text-muted">{{ __('Gender') }}</dt><dd class="text-lg font-extrabold">{{ $patient->gender?->label() ?? '—' }}</dd></div>
                @endif
                @if ($balance > 0)
                    <div class="col-span-2 rounded-2xl bg-rose-600 p-3 text-white"><dt class="text-sm font-bold opacity-90">{{ __('Balance due') }}</dt><dd class="text-2xl font-extrabold ltr-nums">{{ money($balance) }}</dd></div>
                @endif
            </dl>
            @if ($canSeeMedical && $patient->allergyList())
                <div class="mt-3 rounded-2xl border-2 border-rose-600 bg-rose-50 p-3">
                    <p class="flex items-center gap-2 text-base font-extrabold text-rose-800"><x-icon name="allergy" class="size-6" /> {{ __('Allergies') }}</p>
                    <p class="mt-1 text-lg font-bold text-rose-900">{{ implode(is_rtl() ? '، ' : ', ', $patient->allergyList()) }}</p>
                </div>
            @endif
            <div class="mt-4 grid grid-cols-2 gap-2">
                @if ($patient->phone)
                    <a href="tel:{{ $patient->phone }}" class="btn btn-secondary"><x-icon name="phone" class="size-6" /> {{ __('Call') }}</a>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-secondary text-emerald-800"><x-icon name="whatsapp" class="size-6" /> WhatsApp</a>
                @endif
                @can('update', $patient)
                    <button type="button" x-on:click="$dispatch('edit-patient', { id: {{ $patient->id }} })" class="btn btn-primary col-span-2"><x-icon name="edit" class="size-6" /> {{ __('Edit') }}</button>
                @endcan
            </div>
        </section>

        <nav class="card space-y-1 p-2">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['nav-link w-full', 'active' => $tab === $key])>
                    <x-icon :name="['overview' => 'id', 'timeline' => 'clock', 'visits' => 'stethoscope', 'appointments' => 'calendar', 'billing' => 'invoice', 'attachments' => 'paperclip'][$key] ?? 'document'" class="size-6" /> {{ $label }}
                </button>
            @endforeach
        </nav>
    </aside>

    <div class="min-w-0">
        @if ($patient->trashed())
            <div class="mb-4 rounded-2xl bg-rose-600 px-5 py-4 text-lg font-bold text-white">{{ __('This patient file was deleted on :date. It is read-only until restored.', ['date' => Format::date($patient->deleted_at)]) }}</div>
        @endif

        @if ($tab === 'overview')
            <section class="card divide-y-2 divide-line">
                @foreach ([
                    [__('Phone'), $patient->phone, true],
                    [__('Alternative phone'), $patient->phone_alt, true],
                    [__('Gender'), $patient->gender?->label(), false],
                    [__('National ID'), $patient->national_id, true],
                    [__('Birth date'), $patient->birth_date && ! $patient->birth_date_is_estimated ? Format::date($patient->birth_date) : null, false],
                    [__('Occupation'), $patient->occupation, false],
                    [__('Address'), $patient->address, false],
                    [__('Opened on'), Format::date($patient->created_at), false],
                ] as [$label, $value, $ltr])
                    @continue(blank($value))
                    <div class="flex min-h-16 items-center justify-between gap-4 px-5 py-3 text-lg"><span class="font-semibold text-muted">{{ $label }}</span><span @class(['text-end font-bold', 'ltr-nums' => $ltr])>{{ $value }}</span></div>
                @endforeach
                @foreach ($customValues as $custom)
                    <div class="flex min-h-16 items-center justify-between gap-4 px-5 py-3 text-lg"><span class="font-semibold text-muted">{{ $custom['label'] }}</span><span class="text-end font-bold">{{ $custom['value'] }}</span></div>
                @endforeach
                <div class="px-5 py-4 text-lg"><p class="font-semibold text-muted">{{ __('Notes') }}</p><p class="mt-1 font-semibold whitespace-pre-line">{{ $patient->notes ?: '—' }}</p></div>
                @if ($canSeeMedical)
                    <div class="px-5 py-4 text-lg"><p class="font-semibold text-muted">{{ __('Chronic diseases') }}</p><p class="mt-1 font-semibold">{{ $patient->chronic_diseases ?: '—' }}</p></div>
                @endif
            </section>
            @can('delete', $patient)
                @unless ($patient->trashed())
                    <button type="button" wire:click="delete" wire:confirm="{{ __('Delete this patient file? It can be restored later by an admin.') }}" class="btn btn-ghost mt-4 text-rose-700"><x-icon name="trash" class="size-6" /> {{ __('Delete') }}</button>
                @endunless
            @endcan
        @endif

        @if ($tab === 'timeline')
            <ul class="card divide-y-2 divide-line">
                @foreach ($timeline as $entry)
                    <li class="flex min-h-18 items-center gap-4 px-5 py-3">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary-100 text-primary-800"><x-icon :name="$entry->icon" class="size-6" /></span>
                        <div class="min-w-0 flex-1">
                            @if ($entry->url)<a href="{{ $entry->url }}" wire:navigate class="block truncate text-lg font-bold hover:text-primary-700">{{ $entry->title }}</a>@else<p class="truncate text-lg font-bold">{{ $entry->title }}</p>@endif
                            <p class="truncate text-[15px] text-muted">{{ Format::dateTime($entry->date) }} @if ($entry->subtitle)· {{ $entry->subtitle }}@endif</p>
                        </div>
                        @if ($entry->amount)<span class="text-lg font-extrabold">{{ $entry->amount }}</span>@endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($tab === 'visits')
            @if ($canStartVisit)
                <form method="post" action="{{ route('visits.start') }}" class="mb-4">@csrf<input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto"><x-icon name="plus" class="size-6" /> {{ __('New visit') }}</button>
                </form>
            @endif
            <ul class="card divide-y-2 divide-line">
                @forelse ($visits as $visit)
                    <li wire:key="pv-{{ $visit->id }}">
                        <a href="{{ route('visits.show', $visit) }}" wire:navigate class="flex min-h-20 items-center gap-4 px-5 py-3 hover:bg-primary-50">
                            <span class="w-28 shrink-0 text-lg font-extrabold text-primary-700">{{ Format::date($visit->visited_at) }}</span>
                            <span class="min-w-0 flex-1"><span class="block truncate text-lg font-bold">{{ $visit->diagnosis ?: ($visit->chief_complaint ?: __('Visit')) }}</span><span class="block truncate text-[15px] text-muted">{{ $visit->doctor->displayName() }}</span></span>
                            @if ($visit->prescription)<a href="{{ route('print.prescription', $visit->prescription) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="print" class="size-5" /></a>@endif
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-lg font-bold text-muted">{{ __('No visits yet.') }}</li>
                @endforelse
            </ul>
        @endif

        @if ($tab === 'billing')
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-2xl font-extrabold">{{ __('Balance due: :amount', ['amount' => money($balance)]) }}</p>
                @can('create', App\Models\Invoice::class)
                    <a href="{{ route('invoices.create', ['patient' => $patient->id]) }}" wire:navigate class="btn btn-primary btn-lg"><x-icon name="plus" class="size-6" /> {{ __('New invoice') }}</a>
                @endcan
            </div>
            <ul class="card divide-y-2 divide-line">
                @forelse ($invoices as $invoice)
                    <li wire:key="pi-{{ $invoice->id }}">
                        <a href="{{ route('invoices.show', $invoice) }}" wire:navigate class="flex min-h-20 items-center gap-4 px-5 py-3 hover:bg-primary-50">
                            <span class="min-w-0 flex-1"><span class="block text-lg font-bold ltr-nums">{{ $invoice->number }}</span><span class="block text-[15px] text-muted">{{ Format::date($invoice->issued_at) }}</span></span>
                            <span class="text-end"><span class="block text-xl font-extrabold ltr-nums">{{ money($invoice->total) }}</span>@if ($invoice->balance > 0)<span class="block text-[15px] font-bold text-rose-700 ltr-nums">{{ money($invoice->balance) }}</span>@endif</span>
                            <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-lg font-bold text-muted">{{ __('No invoices yet.') }}</li>
                @endforelse
            </ul>
        @endif

        @if ($tab === 'appointments')
            @can('create', App\Models\Appointment::class)
                @unless ($patient->trashed())
                    <button type="button" x-on:click="$dispatch('book-appointment', { patientId: {{ $patient->id }} })" class="btn btn-primary btn-lg mb-4 w-full sm:w-auto"><x-icon name="plus" class="size-6" /> {{ __('Book appointment') }}</button>
                @endunless
            @endcan
            <ul class="card divide-y-2 divide-line">
                @forelse ($appointments as $appointment)
                    <li wire:key="pa-{{ $appointment->id }}">
                        <button type="button" x-on:click="$dispatch('show-appointment', { id: {{ $appointment->id }} })" class="flex min-h-20 w-full items-center gap-4 px-5 py-3 text-start hover:bg-primary-50">
                            <span class="w-28 shrink-0"><span class="block text-lg font-extrabold text-primary-700">{{ Format::date($appointment->starts_at) }}</span><span class="block text-[15px] font-bold ltr-nums">{{ $appointment->starts_at->format('H:i') }}</span></span>
                            <span class="min-w-0 flex-1"><span class="block truncate text-lg font-bold">{{ $appointment->visitType?->name() ?? __('Appointment') }}</span><span class="block truncate text-[15px] text-muted">{{ $appointment->doctor->displayName() }}</span></span>
                            <x-badge :color="$appointment->status->color()">{{ $appointment->status->label() }}</x-badge>
                        </button>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-lg font-bold text-muted">{{ __('No appointments yet.') }}</li>
                @endforelse
            </ul>
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
