@php
    use App\Enums\AppointmentSource;
    use App\Enums\AppointmentStatus;
    use App\Support\Format;
@endphp
{{-- Luxury Minimal waiting room: a concierge list. Large serif queue numbers, hairline rows, no boxes. Polls every 5s. --}}
<div @unless ($showWalkIn) wire:poll.5s @endunless>
    <x-page-header :title="__('Waiting room')" :subtitle="Format::longDate(now())">
        <x-slot:actions>
            @if ($canManage)
                <button type="button" wire:click="openWalkIn" class="btn btn-primary">{{ __('Walk-in patient') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Now serving --}}
    <div class="mb-16 grid border-y border-line md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->doctors as $doctor)
            @continue($doctorId && $doctorId !== $doctor->id)
            @php
                $inRoom = $groups['in_room']->firstWhere('doctor_id', $doctor->id);
                $waitingCount = $groups['waiting']->where('doctor_id', $doctor->id)->count();
            @endphp
            <div class="flex items-end justify-between gap-6 py-8 md:px-8 md:first:ps-0 md:[&:not(:first-child)]:border-s md:[&:not(:first-child)]:border-line" wire:key="doc-{{ $doctor->id }}">
                <div class="min-w-0">
                    <p class="eyebrow text-neutral-400">{{ __('Now with the doctor') }}</p>
                    <p class="mt-2 truncate font-serif text-2xl">{{ $doctor->displayName() }}</p>
                    <p class="mt-1 truncate text-sm text-muted">
                        {{ $inRoom ? $inRoom->patient->name : __('Nobody yet') }} · {{ trans_choice(':count waiting|:count waiting', $waitingCount, ['count' => Format::number($waitingCount)]) }}
                    </p>
                    @if ($canManage)
                        <button type="button" wire:click="callNext({{ $doctor->id }})" @disabled($waitingCount === 0) class="eyebrow mt-4 text-ink underline-offset-8 hover:underline disabled:text-neutral-300 disabled:no-underline">{{ __('Call next') }} {{ is_rtl() ? '←' : '→' }}</button>
                    @endif
                </div>
                <span class="font-serif text-7xl leading-none text-primary-500">{{ $inRoom ? $inRoom->queue_number : '—' }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid gap-16 lg:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-16">
            <section>
                <h2 class="mb-6 font-serif text-3xl">{{ __('Waiting') }} <span class="text-primary-600">{{ Format::number($groups['waiting']->count()) }}</span></h2>
                <ol class="divide-y divide-line border-y border-line">
                    @forelse ($groups['waiting'] as $a)
                        @php $wait = $a->waitingMinutes() ?? 0; @endphp
                        <li class="flex flex-wrap items-center gap-6 py-6" wire:key="w-{{ $a->id }}">
                            <span class="w-14 font-serif text-4xl leading-none text-ink">{{ $a->queue_number }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('patients.show', $a->patient) }}" wire:navigate class="block truncate font-serif text-xl hover:text-primary-700">{{ $a->patient->name }}</a>
                                <p class="mt-1 text-xs text-muted">
                                    {{ $a->doctor->displayName() }} · {{ $a->visitType?->name() }}
                                    @if ($a->source === AppointmentSource::WalkIn) · <span class="text-primary-700">{{ __('Walk-in') }}</span> @endif
                                    · <span @class(['text-rose-700' => $wait >= 30])>{{ trans_choice(':count min|:count min', $wait, ['count' => Format::number($wait)]) }}</span>
                                </p>
                            </div>
                            @if ($canManage)
                                <div class="flex items-center gap-3">
                                    @if ($canStartVisit($a))
                                        <form method="post" action="{{ route('visits.start') }}">
                                            @csrf
                                            <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Start visit') }}</button>
                                        </form>
                                    @else
                                        <button type="button" wire:click="changeStatus({{ $a->id }}, 'in_progress')" class="btn btn-secondary btn-sm">{{ __('Call in') }}</button>
                                    @endif
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'no_show')" wire:confirm="{{ __('Mark this patient as a no-show?') }}" class="btn btn-ghost btn-sm">{{ __('Left') }}</button>
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="py-16 text-center font-serif text-2xl text-muted italic">{{ __('The waiting room is empty.') }}</li>
                    @endforelse
                </ol>
            </section>

            @if ($groups['in_room']->isNotEmpty())
                <section>
                    <h2 class="mb-6 font-serif text-3xl">{{ __('With doctor') }}</h2>
                    <ol class="divide-y divide-line border-y border-line">
                        @foreach ($groups['in_room'] as $a)
                            <li class="flex flex-wrap items-center gap-6 py-5" wire:key="r-{{ $a->id }}">
                                <span class="w-14 font-serif text-3xl leading-none text-primary-500">{{ $a->queue_number }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-serif text-xl">{{ $a->patient->name }}</p>
                                    <p class="text-xs text-muted">{{ $a->doctor->displayName() }} · {{ __('since :time', ['time' => Format::time($a->started_at)]) }}</p>
                                </div>
                                @if ($canStartVisit($a))
                                    <form method="post" action="{{ route('visits.start') }}">
                                        @csrf
                                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Open visit') }}</button>
                                    </form>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'completed')" class="btn btn-secondary btn-sm">{{ __('Complete') }}</button>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <aside>
            <h2 class="mb-6 font-serif text-3xl">{{ __('Expected today') }}</h2>
            <ol class="max-h-[40rem] space-y-5 overflow-y-auto">
                @forelse ($groups['upcoming'] as $a)
                    <li class="grid grid-cols-[3.5rem_1fr] gap-3" wire:key="u-{{ $a->id }}">
                        <span @class(['font-serif text-lg ltr-nums', 'text-rose-700' => $a->starts_at->isPast(), 'text-primary-600' => ! $a->starts_at->isPast()])>{{ $a->starts_at->format('H:i') }}</span>
                        <div class="min-w-0 border-b border-line pb-4">
                            <p class="truncate text-[15px]">{{ $a->patient->name }}</p>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="truncate text-xs text-muted">{{ $a->doctor->displayName() }}</span>
                                @if ($canManage)
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'arrived')" class="eyebrow shrink-0 text-ink hover:text-primary-700">{{ __('Check in') }}</button>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="font-serif text-xl text-muted italic">{{ __('No more appointments today.') }}</li>
                @endforelse
            </ol>

            @if ($groups['done']->isNotEmpty())
                <details class="mt-10 border-t border-line pt-4">
                    <summary class="eyebrow cursor-pointer text-neutral-500">{{ __('Finished') }} ({{ Format::number($groups['done']->count()) }})</summary>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($groups['done'] as $a)
                            <li class="flex items-center gap-3 py-2.5 text-sm" wire:key="d-{{ $a->id }}">
                                <span class="min-w-0 flex-1 truncate">{{ $a->patient->name }}</span>
                                @if ($a->status === AppointmentStatus::Completed && $canInvoice)
                                    <a href="{{ route('invoices.create', ['appointment' => $a->id]) }}" wire:navigate class="text-xs text-primary-700 hover:underline">{{ __('Invoice') }}</a>
                                @endif
                                <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </aside>
    </div>

    {{-- Walk-in --}}
    <x-modal wire:model="showWalkIn" :title="__('Walk-in patient')" max-width="max-w-lg">
        <form id="walkin-form" wire:submit="registerWalkIn" class="space-y-4">
            <div>
                <p class="form-label">{{ __('Patient') }}</p>
                @if ($walkInPatient)
                    <div class="flex items-center justify-between gap-3 rounded-none border border-primary-200 bg-primary-50/50 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $walkInPatient->name }}</p>
                            <p class="text-xs text-muted ltr-nums">#{{ $walkInPatient->file_number }}</p>
                        </div>
                        <button type="button" wire:click="$set('walkInPatientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                    </div>
                @else
                    <input wire:model.live.debounce.300ms="walkInSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}" @class(['form-control', 'is-invalid' => $errors->has('walkInPatientId')])>
                    @if (mb_strlen($walkInSearch) >= 2)
                        <ul class="mt-2 divide-y divide-line rounded-none border border-line">
                            @foreach ($this->walkInResults as $result)
                                <li><button type="button" wire:click="$set('walkInPatientId', {{ $result->id }})" class="w-full px-4 py-2.5 text-start text-sm hover:bg-primary-50/60">{{ $result->name }} <span class="text-xs text-muted ltr-nums">#{{ $result->file_number }}</span></button></li>
                            @endforeach
                            @can('create', App\Models\Patient::class)
                                <li><button type="button" wire:click="newWalkInPatient" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-50/60"><x-icon name="user-plus" class="size-4" /> {{ __('New patient') }}</button></li>
                            @endcan
                        </ul>
                    @endif
                    @error('walkInPatientId') <p class="form-error">{{ $message }}</p> @enderror
                @endif
                @error('patient_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Doctor')" for="wi-doctor" error="walkInDoctorId">
                    <select wire:model="walkInDoctorId" id="wi-doctor" class="form-control">
                        @foreach ($this->doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->displayName() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('Visit type')" for="wi-type" error="walkInVisitTypeId">
                    <select wire:model="walkInVisitTypeId" id="wi-type" class="form-control">
                        @foreach ($visitTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
            @error('doctor_id') <p class="form-error">{{ $message }}</p> @enderror
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="walkin-form" class="btn btn-primary">{{ __('Add to queue') }}</button>
        </x-slot:footer>
    </x-modal>

    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
