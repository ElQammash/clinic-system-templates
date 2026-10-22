@php
    use App\Support\Format;
    $tabEmoji = ['overview' => 'note', 'timeline' => 'timeline', 'visits' => 'stethoscope', 'appointments' => 'calendar', 'billing' => 'invoice', 'attachments' => 'paperclip'];
@endphp
{{-- Warm & Friendly patient file: big header card with emoji fact chips, pill tabs, card lists. --}}
<div>
    <a href="{{ route('patients.index') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-1.5 text-sm font-bold text-muted shadow-(--shadow-soft) hover:text-ink">
        <span>{{ is_rtl() ? '→' : '←' }}</span> {{ __('Patients') }}
    </a>

    @if ($patient->trashed())
        <div class="mb-4 flex items-center gap-3 rounded-3xl bg-rose-50 px-5 py-3 font-bold text-rose-700">
            <span class="emoji text-2xl">🗑️</span>
            {{ __('This patient file was deleted on :date. It is read-only until restored.', ['date' => Format::date($patient->deleted_at)]) }}
        </div>
    @endif

    {{-- Header card --}}
    <section class="card relative overflow-hidden p-6">
        <div class="pointer-events-none absolute -end-16 -top-20 size-64 rounded-full bg-primary-100/60"></div>
        <div class="pointer-events-none absolute -bottom-24 end-40 size-48 rounded-full bg-secondary-100/60"></div>

        <div class="relative flex flex-col gap-5 md:flex-row md:items-center">
            <span @class([
                'grid size-24 shrink-0 place-items-center rounded-full text-3xl font-extrabold ring-8',
                'bg-sky-100 text-sky-700 ring-sky-50' => $patient->gender?->value === 'male',
                'bg-pink-100 text-pink-700 ring-pink-50' => $patient->gender?->value === 'female',
                'bg-cream text-stone-600 ring-canvas' => ! $patient->gender,
            ])>{{ $patient->initials() }}</span>

            <div class="min-w-0 flex-1">
                <h1 class="text-3xl leading-tight font-extrabold text-ink">{{ $patient->name }}</h1>
                <div class="mt-3 flex flex-wrap gap-2 text-sm font-bold">
                    <span class="rounded-full bg-cream px-3 py-1"><x-emoji name="id" /> <span class="ltr-nums">#{{ $patient->file_number }}</span></span>
                    @if ($patient->age())<span class="rounded-full bg-cream px-3 py-1"><x-emoji name="birthday" /> {{ $patient->age() }}</span>@endif
                    @if ($patient->gender)<span class="rounded-full bg-cream px-3 py-1">{{ $patient->gender->value === 'female' ? '👧' : '👦' }} {{ $patient->gender->label() }}</span>@endif
                    @if ($canSeeMedical && $patient->blood_type)<span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700"><x-emoji name="blood" /> <span dir="ltr">{{ $patient->blood_type->value }}</span></span>@endif
                    @if ($patient->phone)<span class="rounded-full bg-cream px-3 py-1"><x-emoji name="phone" /> <span class="ltr-nums">{{ $patient->phone }}</span></span>@endif
                    @if ($balance > 0)<span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700"><x-emoji name="invoice" /> {{ __('Remaining') }}: <span class="ltr-nums">{{ money($balance) }}</span></span>@endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2 md:flex-col md:items-stretch">
                @can('update', $patient)
                    <button type="button" x-on:click="$dispatch('edit-patient', { id: {{ $patient->id }} })" class="btn btn-primary"><x-icon name="edit" class="size-5" /> {{ __('Edit') }}</button>
                @endcan
                @if ($patient->phone)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-secondary text-emerald-700"><x-emoji name="whatsapp" /> WhatsApp</a>
                @endif
                @can('delete', $patient)
                    @unless ($patient->trashed())
                        <button type="button" wire:click="delete" wire:confirm="{{ __('Delete this patient file? It can be restored later by an admin.') }}" class="btn btn-ghost text-rose-600"><x-icon name="trash" class="size-5" /> {{ __('Delete') }}</button>
                    @endunless
                @endcan
            </div>
        </div>
    </section>

    @if ($canSeeMedical && ($patient->allergyList() || $patient->chronicDiseaseList()))
        <div class="mt-4 flex flex-wrap items-center gap-2 rounded-3xl border-2 border-rose-200 bg-rose-50 px-5 py-3">
            <span class="emoji text-2xl">⚠️</span>
            <span class="font-extrabold text-rose-800">{{ __('Alerts') }}:</span>
            @foreach ($patient->allergyList() as $allergy)
                <span class="rounded-full bg-rose-500 px-3 py-0.5 text-sm font-bold text-white">{{ __('Allergy') }}: {{ $allergy }}</span>
            @endforeach
            @foreach ($patient->chronicDiseaseList() as $disease)
                <x-badge color="amber">{{ $disease }}</x-badge>
            @endforeach
        </div>
    @endif

    {{-- Pill tabs --}}
    <div class="my-6 flex gap-2 overflow-x-auto pb-1">
        @foreach ($tabs as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                'flex shrink-0 items-center gap-2 rounded-full px-5 py-2.5 text-[15px] font-bold whitespace-nowrap transition',
                'bg-ink text-white shadow-(--shadow-lift)' => $tab === $key,
                'bg-white text-stone-600 shadow-(--shadow-soft) hover:text-ink' => $tab !== $key,
            ])><x-emoji :name="$tabEmoji[$key] ?? 'sparkles'" /> {{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="grid gap-4 md:grid-cols-2">
            <section class="card p-5">
                <h2 class="mb-3 flex items-center gap-2 text-lg font-extrabold"><x-emoji name="id" /> {{ __('Contact & details') }}</h2>
                <dl class="space-y-2.5 text-[15px]">
                    @foreach ([
                        ['phone', __('Alternative phone'), $patient->phone_alt, true],
                        ['id', __('National ID'), $patient->national_id, true],
                        ['birthday', __('Birth date'), $patient->birth_date && ! $patient->birth_date_is_estimated ? Format::date($patient->birth_date) : null, false],
                        ['doctor', __('Occupation'), $patient->occupation, false],
                        ['location', __('Address'), $patient->address, false],
                        ['calendar', __('Opened on'), Format::date($patient->created_at), false],
                    ] as [$emoji, $label, $value, $ltr])
                        @continue(blank($value))
                        <div class="flex items-start gap-3">
                            <x-emoji :name="$emoji" class="emoji-tile size-9 text-base" />
                            <div><dt class="text-xs font-bold text-muted">{{ $label }}</dt><dd @class(['font-semibold', 'ltr-nums' => $ltr])>{{ $value }}</dd></div>
                        </div>
                    @endforeach
                    @foreach ($customValues as $custom)
                        <div class="flex items-start gap-3">
                            <x-emoji name="fields" class="emoji-tile size-9 text-base" />
                            <div><dt class="text-xs font-bold text-muted">{{ $custom['label'] }}</dt><dd class="font-semibold">{{ $custom['value'] }}</dd></div>
                        </div>
                    @endforeach
                </dl>
            </section>
            <section class="card p-5">
                <h2 class="mb-3 flex items-center gap-2 text-lg font-extrabold"><x-emoji name="note" /> {{ __('Notes') }}</h2>
                <p class="whitespace-pre-line text-[15px]">{{ $patient->notes ?: '—' }}</p>
                @if ($canSeeMedical)
                    <h2 class="mt-5 mb-3 flex items-center gap-2 text-lg font-extrabold"><x-emoji name="heart" /> {{ __('Medical background') }}</h2>
                    <dl class="space-y-2 text-[15px]">
                        <div><dt class="text-xs font-bold text-muted">{{ __('Allergies') }}</dt><dd class="font-semibold">{{ $patient->allergies ?: __('No known allergies') }}</dd></div>
                        <div><dt class="text-xs font-bold text-muted">{{ __('Chronic diseases') }}</dt><dd class="font-semibold">{{ $patient->chronic_diseases ?: '—' }}</dd></div>
                    </dl>
                @endif
            </section>
        </div>
    @endif

    @if ($tab === 'timeline')
        <ol class="relative space-y-3 ps-8 before:absolute before:inset-y-2 before:start-3.5 before:w-1 before:rounded-full before:bg-line">
            @forelse ($timeline as $entry)
                <li class="relative">
                    <span class="absolute -start-8 top-3 grid size-8 place-items-center rounded-full bg-white shadow-(--shadow-soft)"><x-icon :name="$entry->icon" class="size-4 text-primary-500" /></span>
                    <div class="card p-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            @if ($entry->url)<a href="{{ $entry->url }}" wire:navigate class="font-bold text-ink hover:text-primary-600">{{ $entry->title }}</a>@else<p class="font-bold">{{ $entry->title }}</p>@endif
                            <span class="text-sm text-muted">{{ Format::dateTime($entry->date) }}</span>
                        </div>
                        @if ($entry->subtitle || $entry->amount)<p class="mt-1 text-sm text-muted">{{ $entry->subtitle }} @if ($entry->amount)<span class="font-bold text-ink">· {{ $entry->amount }}</span>@endif</p>@endif
                    </div>
                </li>
            @empty
                <li class="card p-10 text-center"><span class="emoji text-4xl">🌱</span><p class="mt-2 font-bold text-muted">{{ __('Nothing here yet.') }}</p></li>
            @endforelse
        </ol>
    @endif

    @if ($tab === 'visits')
        <div class="mb-4 flex justify-end">
            @if ($canStartVisit)
                <form method="post" action="{{ route('visits.start') }}">@csrf<input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <button type="submit" class="btn btn-primary"><x-icon name="plus" class="size-5" /> {{ __('New visit') }}</button>
                </form>
            @endif
        </div>
        <div class="grid gap-3 md:grid-cols-2">
            @forelse ($visits as $visit)
                <a href="{{ route('visits.show', $visit) }}" wire:navigate wire:key="pv-{{ $visit->id }}" class="card block p-5 transition hover:-translate-y-0.5 hover:shadow-(--shadow-lift)">
                    <div class="flex items-center justify-between gap-2">
                        <span class="rounded-full bg-cream px-3 py-0.5 text-sm font-bold">{{ Format::date($visit->visited_at) }}</span>
                        @if ($visit->prescription)<span class="text-sm font-bold text-primary-600"><x-emoji name="pill" /> {{ $visit->prescription->items->count() }}</span>@endif
                    </div>
                    <p class="mt-2 text-lg font-extrabold">{{ $visit->diagnosis ?: ($visit->chief_complaint ?: __('Visit')) }}</p>
                    <p class="text-sm text-muted">{{ $visit->doctor->displayName() }}
                        @if ($visit->bloodPressure()) · <span class="ltr-nums">BP {{ $visit->bloodPressure() }}</span>@endif
                        @if ($visit->weight) · <span class="ltr-nums">{{ Format::number($visit->weight, 1) }} kg</span>@endif
                    </p>
                </a>
            @empty
                <div class="card p-10 text-center md:col-span-2"><span class="emoji text-4xl">🩺</span><p class="mt-2 font-bold text-muted">{{ __('No visits yet.') }}</p></div>
            @endforelse
        </div>
    @endif

    @if ($tab === 'billing')
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p @class(['rounded-full px-4 py-2 font-extrabold', 'bg-rose-100 text-rose-700' => $balance > 0, 'bg-emerald-100 text-emerald-800' => $balance <= 0])>
                {{ $balance > 0 ? '🧾' : '🎉' }} {{ __('Balance due: :amount', ['amount' => money($balance)]) }}
            </p>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create', ['patient' => $patient->id]) }}" wire:navigate class="btn btn-primary"><x-icon name="plus" class="size-5" /> {{ __('New invoice') }}</a>
            @endcan
        </div>
        <div class="space-y-2">
            @forelse ($invoices as $invoice)
                <a href="{{ route('invoices.show', $invoice) }}" wire:navigate wire:key="pi-{{ $invoice->id }}" class="card flex flex-wrap items-center gap-4 px-5 py-4 transition hover:shadow-(--shadow-lift)">
                    <x-emoji name="invoice" class="emoji-tile size-11 text-xl" />
                    <div class="min-w-0 flex-1">
                        <p class="font-extrabold ltr-nums">{{ $invoice->number }}</p>
                        <p class="text-sm text-muted">{{ Format::date($invoice->issued_at) }}</p>
                    </div>
                    <div class="text-end">
                        <p class="font-extrabold ltr-nums">{{ money($invoice->total) }}</p>
                        @if ($invoice->balance > 0)<p class="text-sm font-bold text-rose-600 ltr-nums">{{ __('Remaining') }} {{ money($invoice->balance) }}</p>@endif
                    </div>
                    <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
                </a>
            @empty
                <div class="card p-10 text-center"><span class="emoji text-4xl">🧾</span><p class="mt-2 font-bold text-muted">{{ __('No invoices yet.') }}</p></div>
            @endforelse
        </div>
    @endif

    @if ($tab === 'appointments')
        <div class="mb-4 flex justify-end">
            @can('create', App\Models\Appointment::class)
                @unless ($patient->trashed())
                    <button type="button" x-on:click="$dispatch('book-appointment', { patientId: {{ $patient->id }} })" class="btn btn-primary"><x-icon name="plus" class="size-5" /> {{ __('Book appointment') }}</button>
                @endunless
            @endcan
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($appointments as $appointment)
                <button type="button" wire:key="pa-{{ $appointment->id }}" x-on:click="$dispatch('show-appointment', { id: {{ $appointment->id }} })" class="card flex items-center gap-4 p-4 text-start transition hover:shadow-(--shadow-lift)">
                    <div class="grid size-16 shrink-0 place-content-center rounded-3xl bg-primary-50 text-center text-primary-700">
                        <span class="text-2xl leading-none font-extrabold">{{ $appointment->starts_at->format('j') }}</span>
                        <span class="text-xs font-bold">{{ $appointment->starts_at->translatedFormat('M') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold">{{ $appointment->visitType?->name() ?? __('Appointment') }}</p>
                        <p class="text-sm text-muted"><span class="ltr-nums">{{ Format::time($appointment->starts_at) }}</span> · {{ $appointment->doctor->displayName() }}</p>
                        <x-badge :color="$appointment->status->color()" class="mt-1">{{ $appointment->status->label() }}</x-badge>
                    </div>
                </button>
            @empty
                <div class="card p-10 text-center sm:col-span-2 lg:col-span-3"><span class="emoji text-4xl">📅</span><p class="mt-2 font-bold text-muted">{{ __('No appointments yet.') }}</p></div>
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
