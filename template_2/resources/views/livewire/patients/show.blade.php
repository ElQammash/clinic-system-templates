@php use App\Support\Format; @endphp
{{-- Dark Pro patient file: fixed meta panel on the start side + tabbed data tables. --}}
<div>
    <div class="mb-4 flex flex-wrap items-center gap-3 border-b border-line pb-4">
        <nav class="flex items-center gap-2 text-xs text-muted">
            <a href="{{ route('patients.index') }}" wire:navigate class="hover:text-ink">{{ __('Patients') }}</a>
            <span>/</span>
            <span class="font-mono text-ink" dir="ltr">#{{ $patient->file_number }}</span>
        </nav>
        <div class="ms-auto flex flex-wrap gap-2">
            @if ($patient->phone)
                <a href="tel:{{ $patient->phone }}" class="btn btn-secondary btn-sm"><x-icon name="phone" class="size-3.5" /> <span class="ltr-nums">{{ $patient->phone }}</span></a>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm text-emerald-500"><x-icon name="whatsapp" class="size-3.5" /> WhatsApp</a>
            @endif
            @can('update', $patient)
                <button type="button" x-on:click="$dispatch('edit-patient', { id: {{ $patient->id }} })" class="btn btn-primary btn-sm"><x-icon name="edit" class="size-3.5" /> {{ __('Edit') }}</button>
            @endcan
            @can('delete', $patient)
                @unless ($patient->trashed())
                    <button type="button" wire:click="delete" wire:confirm="{{ __('Delete this patient file? It can be restored later by an admin.') }}" class="btn btn-ghost btn-sm text-rose-500" title="{{ __('Delete') }}"><x-icon name="trash" class="size-3.5" /></button>
                @endunless
            @endcan
        </div>
    </div>

    @if ($patient->trashed())
        <div class="mb-4 border-s-2 border-rose-500 bg-rose-500/10 px-3 py-2 text-xs text-rose-500">
            {{ __('This patient file was deleted on :date. It is read-only until restored.', ['date' => Format::date($patient->deleted_at)]) }}
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[18rem_1fr]">
        {{-- Meta panel --}}
        <aside class="space-y-4 lg:sticky lg:top-16 lg:self-start">
            <section class="card p-4">
                <div class="flex items-center gap-3">
                    <span @class([
                        'grid size-12 shrink-0 place-items-center rounded-sm font-mono text-base font-bold',
                        'bg-sky-500/15 text-sky-500' => $patient->gender?->value === 'male',
                        'bg-pink-500/15 text-pink-500' => $patient->gender?->value === 'female',
                        'bg-subtle text-muted' => ! $patient->gender,
                    ])>{{ $patient->initials() }}</span>
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-semibold text-ink">{{ $patient->name }}</h1>
                        <p class="font-mono text-[11px] text-muted" dir="ltr">#{{ $patient->file_number }}</p>
                    </div>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-sm border border-line bg-line text-center">
                    <div class="bg-subtle px-2 py-2"><dt class="text-[10px] text-muted uppercase">{{ __('Age') }}</dt><dd class="mt-0.5 text-[13px] font-semibold text-ink">{{ $patient->age() ?: '—' }}</dd></div>
                    <div class="bg-subtle px-2 py-2"><dt class="text-[10px] text-muted uppercase">{{ __('Gender') }}</dt><dd class="mt-0.5 text-[13px] font-semibold text-ink">{{ $patient->gender?->label() ?? '—' }}</dd></div>
                    @if ($canSeeMedical)
                        <div class="bg-subtle px-2 py-2"><dt class="text-[10px] text-muted uppercase">{{ __('Blood type') }}</dt><dd class="mt-0.5 font-mono text-[13px] font-semibold text-rose-500" dir="ltr">{{ $patient->blood_type?->value ?? '—' }}</dd></div>
                    @endif
                    @if ($balance > 0 || ! $canSeeMedical)
                        <div class="bg-subtle px-2 py-2"><dt class="text-[10px] text-muted uppercase">{{ __('Remaining') }}</dt><dd class="mt-0.5 font-mono text-[13px] font-semibold {{ $balance > 0 ? 'text-rose-500' : 'text-ink' }}">{{ money($balance) }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($canSeeMedical && ($patient->allergyList() || $patient->chronicDiseaseList()))
                <section class="card border-rose-500/40 p-4">
                    <h2 class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold tracking-wider text-rose-500 uppercase"><x-icon name="allergy" class="size-3.5" /> {{ __('Alerts') }}</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($patient->allergyList() as $allergy)
                            <span class="rounded-[3px] bg-rose-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">{{ __('Allergy') }}: {{ $allergy }}</span>
                        @endforeach
                        @foreach ($patient->chronicDiseaseList() as $disease)
                            <x-badge color="amber">{{ $disease }}</x-badge>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="card divide-y divide-line text-[13px]">
                @foreach ([
                    [__('Phone'), $patient->phone, true],
                    [__('Alternative phone'), $patient->phone_alt, true],
                    [__('National ID'), $patient->national_id, true],
                    [__('Birth date'), $patient->birth_date && ! $patient->birth_date_is_estimated ? Format::date($patient->birth_date) : null, false],
                    [__('Occupation'), $patient->occupation, false],
                    [__('Address'), $patient->address, false],
                ] as [$label, $value, $mono])
                    @continue(blank($value))
                    <div class="flex justify-between gap-3 px-4 py-2"><span class="text-muted">{{ $label }}</span><span @class(['text-end text-ink', 'ltr-nums' => $mono])>{{ $value }}</span></div>
                @endforeach
                @foreach ($customValues as $custom)
                    <div class="flex justify-between gap-3 px-4 py-2"><span class="text-muted">{{ $custom['label'] }}</span><span class="text-end text-ink">{{ $custom['value'] }}</span></div>
                @endforeach
                <div class="flex justify-between gap-3 px-4 py-2"><span class="text-muted">{{ __('Opened on') }}</span><span class="text-ink">{{ Format::date($patient->created_at) }}</span></div>
            </section>
        </aside>

        {{-- Data --}}
        <div class="min-w-0">
            <div class="mb-3 flex gap-px overflow-x-auto rounded-sm border border-line bg-line p-px">
                @foreach ($tabs as $key => $label)
                    <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                        'flex-1 shrink-0 px-3 py-1.5 text-xs font-semibold whitespace-nowrap transition',
                        'bg-primary-500 text-slate-950' => $tab === $key,
                        'bg-surface text-muted hover:text-ink' => $tab !== $key,
                    ])>{{ $label }}</button>
                @endforeach
            </div>

            @if ($tab === 'overview')
                <section class="card p-4">
                    <h2 class="mb-2 text-[11px] font-semibold tracking-wider text-muted uppercase">{{ __('Notes') }}</h2>
                    <p class="text-[13px] whitespace-pre-line text-ink">{{ $patient->notes ?: '—' }}</p>
                    @if ($canSeeMedical)
                        <h2 class="mt-5 mb-2 text-[11px] font-semibold tracking-wider text-muted uppercase">{{ __('Medical background') }}</h2>
                        <dl class="grid gap-3 text-[13px] sm:grid-cols-2">
                            <div><dt class="text-muted">{{ __('Allergies') }}</dt><dd class="text-ink">{{ $patient->allergies ?: __('No known allergies') }}</dd></div>
                            <div><dt class="text-muted">{{ __('Chronic diseases') }}</dt><dd class="text-ink">{{ $patient->chronic_diseases ?: '—' }}</dd></div>
                        </dl>
                    @endif
                </section>
            @endif

            @if ($tab === 'timeline')
                <section class="card overflow-hidden">
                    <table class="table-clean">
                        <thead><tr><th class="w-40">{{ __('Date') }}</th><th>{{ __('Event') }}</th><th class="hidden md:table-cell">{{ __('Details') }}</th></tr></thead>
                        <tbody>
                            @foreach ($timeline as $entry)
                                <tr>
                                    <td class="font-mono text-xs text-muted" dir="ltr">{{ $entry->date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="inline-flex items-center gap-2">
                                            <x-icon :name="$entry->icon" class="size-4 text-primary-400" />
                                            @if ($entry->url)<a href="{{ $entry->url }}" wire:navigate class="text-ink hover:text-primary-400">{{ $entry->title }}</a>@else<span class="text-ink">{{ $entry->title }}</span>@endif
                                        </span>
                                    </td>
                                    <td class="hidden text-xs md:table-cell">{{ $entry->subtitle }} @if ($entry->amount)<span class="num text-ink">· {{ $entry->amount }}</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif

            @if ($tab === 'visits')
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-4 py-2">
                        <h2 class="text-[13px] font-semibold text-ink">{{ __('Visits') }}</h2>
                        @if ($canStartVisit)
                            <form method="post" action="{{ route('visits.start') }}">@csrf<input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                <button type="submit" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-3.5" /> {{ __('New visit') }}</button>
                            </form>
                        @endif
                    </div>
                    <table class="table-clean">
                        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Diagnosis') }}</th><th class="hidden md:table-cell">{{ __('Doctor') }}</th><th class="hidden lg:table-cell">BP / kg</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($visits as $visit)
                                <tr wire:key="pv-{{ $visit->id }}">
                                    <td class="font-mono text-xs" dir="ltr">{{ $visit->visited_at->format('Y-m-d') }}</td>
                                    <td class="max-w-xs truncate"><a href="{{ route('visits.show', $visit) }}" wire:navigate class="text-ink hover:text-primary-400">{{ $visit->diagnosis ?: ($visit->chief_complaint ?: __('Visit')) }}</a>
                                        @if ($visit->prescription)<span class="ms-1 font-mono text-[11px] text-primary-400">℞{{ $visit->prescription->items->count() }}</span>@endif</td>
                                    <td class="hidden md:table-cell">{{ $visit->doctor->displayName() }}</td>
                                    <td class="hidden font-mono text-xs lg:table-cell" dir="ltr">{{ $visit->bloodPressure() ?? '—' }} · {{ $visit->weight ? Format::number($visit->weight, 1) : '—' }}</td>
                                    <td class="text-end">@if ($visit->prescription)<a href="{{ route('print.prescription', $visit->prescription) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="print" class="size-3.5" /></a>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-xs text-muted">{{ __('No visits yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            @if ($tab === 'billing')
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-4 py-2">
                        <h2 class="text-[13px] font-semibold text-ink">{{ __('Billing') }} <span class="ms-2 font-mono text-xs {{ $balance > 0 ? 'text-rose-500' : 'text-muted' }}">{{ __('Balance due: :amount', ['amount' => money($balance)]) }}</span></h2>
                        @can('create', App\Models\Invoice::class)
                            <a href="{{ route('invoices.create', ['patient' => $patient->id]) }}" wire:navigate class="btn btn-primary btn-sm"><x-icon name="plus" class="size-3.5" /> {{ __('New invoice') }}</a>
                        @endcan
                    </div>
                    <table class="table-clean">
                        <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">{{ __('Remaining') }}</th><th>{{ __('Status') }}</th></tr></thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr wire:key="pi-{{ $invoice->id }}" class="cursor-pointer" onclick="Livewire.navigate('{{ route('invoices.show', $invoice) }}')">
                                    <td class="font-mono text-xs text-ink" dir="ltr">{{ $invoice->number }}</td>
                                    <td class="font-mono text-xs" dir="ltr">{{ $invoice->issued_at->format('Y-m-d') }}</td>
                                    <td class="num text-end text-ink">{{ money($invoice->total) }}</td>
                                    <td class="num text-end {{ $invoice->balance > 0 ? 'text-rose-500' : '' }}">{{ money($invoice->balance) }}</td>
                                    <td><x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-xs text-muted">{{ __('No invoices yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            @if ($tab === 'appointments')
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-4 py-2">
                        <h2 class="text-[13px] font-semibold text-ink">{{ __('Appointments') }}</h2>
                        @can('create', App\Models\Appointment::class)
                            @unless ($patient->trashed())
                                <button type="button" x-on:click="$dispatch('book-appointment', { patientId: {{ $patient->id }} })" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-3.5" /> {{ __('Book appointment') }}</button>
                            @endunless
                        @endcan
                    </div>
                    <table class="table-clean">
                        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Time') }}</th><th>{{ __('Visit type') }}</th><th class="hidden md:table-cell">{{ __('Doctor') }}</th><th>{{ __('Status') }}</th></tr></thead>
                        <tbody>
                            @forelse ($appointments as $appointment)
                                <tr wire:key="pa-{{ $appointment->id }}" class="cursor-pointer" x-on:click="$dispatch('show-appointment', { id: {{ $appointment->id }} })">
                                    <td class="font-mono text-xs" dir="ltr">{{ $appointment->starts_at->format('Y-m-d') }}</td>
                                    <td class="font-mono text-xs" dir="ltr">{{ $appointment->starts_at->format('H:i') }}</td>
                                    <td class="text-ink">{{ $appointment->visitType?->name() ?? __('Appointment') }}</td>
                                    <td class="hidden md:table-cell">{{ $appointment->doctor->displayName() }}</td>
                                    <td><x-badge :color="$appointment->status->color()">{{ $appointment->status->label() }}</x-badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-xs text-muted">{{ __('No appointments yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            @if ($tab === 'attachments')
                <livewire:patients.attachment-manager :patient="$patient" :key="'attachments-'.$patient->id" />
            @endif
        </div>
    </div>

    <livewire:patients.patient-editor :redirect-on-create="false" />
    @can('viewAny', App\Models\Appointment::class)
        <livewire:appointments.booking-form />
        <livewire:appointments.appointment-details />
    @endcan
</div>
