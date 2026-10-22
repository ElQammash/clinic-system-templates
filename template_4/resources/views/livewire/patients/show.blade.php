@php use App\Support\Format; @endphp
{{-- Luxury Minimal patient file: an editorial dossier — serif name, hairline fact grid, text tabs with a gold underline. --}}
<div>
    <a href="{{ route('patients.index') }}" wire:navigate class="eyebrow text-neutral-400 hover:text-ink">{{ is_rtl() ? '→' : '←' }} {{ __('Patients') }}</a>

    @if ($patient->trashed())
        <p class="mt-6 border-s border-rose-600 ps-4 text-sm text-rose-800">{{ __('This patient file was deleted on :date. It is read-only until restored.', ['date' => Format::date($patient->deleted_at)]) }}</p>
    @endif

    <header class="mt-8 flex flex-col gap-8 border-b border-line pb-10 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="eyebrow">{{ __('File no.') }} <span class="ltr-nums">{{ $patient->file_number }}</span></p>
            <h1 class="mt-3 font-serif text-5xl leading-tight sm:text-6xl">{{ $patient->name }}</h1>
            @if ($canSeeMedical && ($patient->allergyList() || $patient->chronicDiseaseList()))
                <p class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    @foreach ($patient->allergyList() as $allergy)
                        <span class="text-rose-700"><span class="me-2 inline-block size-1.5 rounded-full bg-rose-600 align-middle"></span>{{ __('Allergy') }}: {{ $allergy }}</span>
                    @endforeach
                    @foreach ($patient->chronicDiseaseList() as $disease)
                        <span class="text-amber-800"><span class="me-2 inline-block size-1.5 rounded-full bg-amber-500 align-middle"></span>{{ $disease }}</span>
                    @endforeach
                </p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            @if ($patient->phone)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">WhatsApp</a>
            @endif
            @can('update', $patient)
                <button type="button" x-on:click="$dispatch('edit-patient', { id: {{ $patient->id }} })" class="btn btn-primary btn-sm">{{ __('Edit') }}</button>
            @endcan
            @can('delete', $patient)
                @unless ($patient->trashed())
                    <button type="button" wire:click="delete" wire:confirm="{{ __('Delete this patient file? It can be restored later by an admin.') }}" class="btn btn-ghost btn-sm text-rose-700">{{ __('Delete') }}</button>
                @endunless
            @endcan
        </div>
    </header>

    {{-- Fact grid --}}
    <dl class="grid grid-cols-2 border-b border-line sm:grid-cols-3 lg:grid-cols-6">
        @foreach (array_filter([
            [__('Age'), $patient->age() ?: '—', false],
            [__('Gender'), $patient->gender?->label() ?? '—', false],
            $canSeeMedical ? [__('Blood type'), $patient->blood_type?->value ?? '—', true] : null,
            [__('Phone'), $patient->phone ?: '—', true],
            [__('Opened on'), Format::date($patient->created_at), false],
            $balance > 0 ? [__('Remaining'), money($balance), true] : null,
        ]) as [$label, $value, $ltr])
            <div class="border-line py-6 pe-4 [&:not(:first-child)]:ps-0 lg:[&:not(:first-child)]:border-s lg:[&:not(:first-child)]:ps-6">
                <dt class="eyebrow text-neutral-400">{{ $label }}</dt>
                <dd @class(['mt-2 font-serif text-2xl', 'ltr-nums' => $ltr, 'text-rose-700' => $label === __('Remaining')])>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    {{-- Text tabs --}}
    <nav class="mt-10 mb-10 flex gap-8 overflow-x-auto border-b border-line">
        @foreach ($tabs as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                '-mb-px shrink-0 border-b pb-3 text-[13px] whitespace-nowrap transition',
                'border-primary-500 text-ink' => $tab === $key,
                'border-transparent text-neutral-400 hover:text-ink' => $tab !== $key,
            ])>{{ $label }}</button>
        @endforeach
    </nav>

    @if ($tab === 'overview')
        <div class="grid gap-16 lg:grid-cols-2">
            <section>
                <h2 class="mb-6 font-serif text-3xl">{{ __('Details') }}</h2>
                <dl class="divide-y divide-line border-y border-line text-[15px]">
                    @foreach ([
                        [__('Alternative phone'), $patient->phone_alt, true],
                        [__('National ID'), $patient->national_id, true],
                        [__('Birth date'), $patient->birth_date && ! $patient->birth_date_is_estimated ? Format::date($patient->birth_date) : null, false],
                        [__('Occupation'), $patient->occupation, false],
                        [__('Address'), $patient->address, false],
                    ] as [$label, $value, $ltr])
                        @continue(blank($value))
                        <div class="flex justify-between gap-6 py-4"><dt class="text-muted">{{ $label }}</dt><dd @class(['text-end', 'ltr-nums' => $ltr])>{{ $value }}</dd></div>
                    @endforeach
                    @foreach ($customValues as $custom)
                        <div class="flex justify-between gap-6 py-4"><dt class="text-muted">{{ $custom['label'] }}</dt><dd class="text-end">{{ $custom['value'] }}</dd></div>
                    @endforeach
                </dl>
            </section>
            <section>
                <h2 class="mb-6 font-serif text-3xl">{{ __('Notes') }}</h2>
                <p class="font-serif text-xl leading-relaxed whitespace-pre-line text-neutral-700">{{ $patient->notes ?: '—' }}</p>
                @if ($canSeeMedical)
                    <h2 class="mt-12 mb-6 font-serif text-3xl">{{ __('Medical background') }}</h2>
                    <dl class="divide-y divide-line border-y border-line text-[15px]">
                        <div class="flex justify-between gap-6 py-4"><dt class="text-muted">{{ __('Allergies') }}</dt><dd class="text-end">{{ $patient->allergies ?: __('No known allergies') }}</dd></div>
                        <div class="flex justify-between gap-6 py-4"><dt class="text-muted">{{ __('Chronic diseases') }}</dt><dd class="text-end">{{ $patient->chronic_diseases ?: '—' }}</dd></div>
                    </dl>
                @endif
            </section>
        </div>
    @endif

    @if ($tab === 'timeline')
        <ol class="border-s border-line">
            @foreach ($timeline as $entry)
                <li class="relative ps-10 pb-10">
                    <span class="absolute -start-[3px] top-2 size-1.5 rounded-full bg-primary-500"></span>
                    <p class="eyebrow text-neutral-400">{{ Format::dateTime($entry->date) }}</p>
                    @if ($entry->url)
                        <a href="{{ $entry->url }}" wire:navigate class="mt-2 block font-serif text-2xl hover:text-primary-700">{{ $entry->title }}</a>
                    @else
                        <p class="mt-2 font-serif text-2xl">{{ $entry->title }}</p>
                    @endif
                    @if ($entry->subtitle || $entry->amount)<p class="mt-1 text-sm text-muted">{{ $entry->subtitle }} @if ($entry->amount)— <span class="text-ink">{{ $entry->amount }}</span>@endif</p>@endif
                </li>
            @endforeach
        </ol>
    @endif

    @if ($tab === 'visits')
        @if ($canStartVisit)
            <form method="post" action="{{ route('visits.start') }}" class="mb-8 flex justify-end">@csrf<input type="hidden" name="patient_id" value="{{ $patient->id }}">
                <button type="submit" class="btn btn-primary">{{ __('New visit') }}</button>
            </form>
        @endif
        <div class="divide-y divide-line border-y border-line">
            @forelse ($visits as $visit)
                <a href="{{ route('visits.show', $visit) }}" wire:navigate wire:key="pv-{{ $visit->id }}" class="group grid gap-2 py-6 sm:grid-cols-[10rem_1fr_auto] sm:items-baseline sm:gap-8">
                    <span class="font-serif text-lg text-primary-600">{{ Format::date($visit->visited_at) }}</span>
                    <span>
                        <span class="block font-serif text-2xl group-hover:text-primary-700">{{ $visit->diagnosis ?: ($visit->chief_complaint ?: __('Visit')) }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ $visit->doctor->displayName() }}@if ($visit->bloodPressure()) · <span class="ltr-nums">BP {{ $visit->bloodPressure() }}</span>@endif @if ($visit->weight) · <span class="ltr-nums">{{ Format::number($visit->weight, 1) }} kg</span>@endif</span>
                    </span>
                    @if ($visit->prescription)<span class="eyebrow text-neutral-400">℞ {{ $visit->prescription->items->count() }}</span>@endif
                </a>
            @empty
                <p class="py-16 text-center font-serif text-2xl text-muted italic">{{ __('No visits yet.') }}</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'billing')
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <p><span class="eyebrow text-neutral-400">{{ __('Balance') }}</span> <span @class(['ms-3 font-serif text-3xl ltr-nums', 'text-rose-700' => $balance > 0])>{{ money($balance) }}</span></p>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create', ['patient' => $patient->id]) }}" wire:navigate class="btn btn-primary">{{ __('New invoice') }}</a>
            @endcan
        </div>
        <table class="table-clean">
            <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">{{ __('Remaining') }}</th><th>{{ __('Status') }}</th></tr></thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr wire:key="pi-{{ $invoice->id }}" class="cursor-pointer" onclick="Livewire.navigate('{{ route('invoices.show', $invoice) }}')">
                        <td class="ltr-nums">{{ $invoice->number }}</td>
                        <td>{{ Format::date($invoice->issued_at) }}</td>
                        <td class="text-end font-serif text-lg ltr-nums">{{ money($invoice->total) }}</td>
                        <td @class(['text-end ltr-nums', 'text-rose-700' => $invoice->balance > 0])>{{ money($invoice->balance) }}</td>
                        <td><x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-16 text-center font-serif text-2xl text-muted italic">{{ __('No invoices yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($tab === 'appointments')
        @can('create', App\Models\Appointment::class)
            @unless ($patient->trashed())
                <div class="mb-8 flex justify-end">
                    <button type="button" x-on:click="$dispatch('book-appointment', { patientId: {{ $patient->id }} })" class="btn btn-primary">{{ __('Book appointment') }}</button>
                </div>
            @endunless
        @endcan
        <div class="divide-y divide-line border-y border-line">
            @forelse ($appointments as $appointment)
                <button type="button" wire:key="pa-{{ $appointment->id }}" x-on:click="$dispatch('show-appointment', { id: {{ $appointment->id }} })" class="grid w-full gap-2 py-5 text-start sm:grid-cols-[10rem_1fr_auto] sm:items-baseline sm:gap-8">
                    <span class="font-serif text-lg text-primary-600">{{ Format::date($appointment->starts_at) }} <span class="text-sm text-muted ltr-nums">{{ $appointment->starts_at->format('H:i') }}</span></span>
                    <span><span class="block text-[15px]">{{ $appointment->visitType?->name() ?? __('Appointment') }}</span><span class="text-sm text-muted">{{ $appointment->doctor->displayName() }}</span></span>
                    <x-badge :color="$appointment->status->color()">{{ $appointment->status->label() }}</x-badge>
                </button>
            @empty
                <p class="py-16 text-center font-serif text-2xl text-muted italic">{{ __('No appointments yet.') }}</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'attachments')
        <livewire:patients.attachment-manager :patient="$patient" :key="'attachments-'.$patient->id" />
    @endif

    <livewire:patients.patient-editor :redirect-on-create="false" />
    @can('viewAny', App\Models\Appointment::class)
        <livewire:appointments.booking-form />
        <livewire:appointments.appointment-details />
    @endcan
</div>
