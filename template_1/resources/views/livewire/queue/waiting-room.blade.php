@php
    use App\Enums\AppointmentStatus;
    use App\Support\Format;
@endphp
{{-- Live refresh every 5 seconds (paused while the walk-in form is open). --}}
<div @unless ($showWalkIn) wire:poll.5s @endunless>
    <x-page-header :title="__('Waiting room')" :subtitle="Format::longDate(now())">
        <x-slot:actions>
            @if ($canManage)
                <button type="button" wire:click="openWalkIn" class="btn btn-primary"><x-icon name="user-plus" class="size-5" /> {{ __('Walk-in patient') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Doctors: now serving + call next --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->doctors as $doctor)
            @continue($doctorId && $doctorId !== $doctor->id)
            @php
                $inRoom = $groups['in_room']->firstWhere('doctor_id', $doctor->id);
                $waitingCount = $groups['waiting']->where('doctor_id', $doctor->id)->count();
            @endphp
            <div class="card overflow-hidden" wire:key="doc-{{ $doctor->id }}">
                <div class="h-1.5" style="background: {{ $doctor->doctorProfile?->color }}"></div>
                <div class="p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{{ $doctor->displayName() }}</p>
                            <p class="text-xs text-muted">{{ trans_choice(':count waiting|:count waiting', $waitingCount, ['count' => Format::number($waitingCount)]) }}</p>
                        </div>
                        @if ($canManage)
                            <button type="button" wire:click="callNext({{ $doctor->id }})" @disabled($waitingCount === 0) class="btn btn-primary btn-sm shrink-0">
                                <x-icon name="sparkles" class="size-4" /> {{ __('Call next') }}
                            </button>
                        @endif
                    </div>
                    <div class="mt-4 rounded-xl bg-violet-50/70 px-4 py-3">
                        <p class="text-xs font-medium text-violet-700">{{ __('Now with the doctor') }}</p>
                        @if ($inRoom)
                            <p class="mt-1 flex items-center gap-2 font-semibold text-ink">
                                <span class="grid size-7 place-items-center rounded-full bg-violet-600 text-xs text-white">{{ Format::number($inRoom->queue_number) }}</span>
                                <a href="{{ route('patients.show', $inRoom->patient) }}" wire:navigate class="truncate hover:text-primary-700">{{ $inRoom->patient->name }}</a>
                            </p>
                        @else
                            <p class="mt-1 text-sm text-muted">{{ __('Nobody yet') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Waiting --}}
        <section class="card overflow-hidden lg:col-span-2">
            <header class="flex items-center gap-2 border-b border-line px-5 py-4">
                <span class="size-2.5 rounded-full bg-amber-500"></span>
                <h2 class="font-semibold text-ink">{{ __('Waiting') }}</h2>
                <x-badge color="amber">{{ Format::number($groups['waiting']->count()) }}</x-badge>
            </header>
            <ul class="divide-y divide-line">
                @forelse ($groups['waiting'] as $a)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4" wire:key="w-{{ $a->id }}">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-lg font-bold text-amber-800">{{ Format::number($a->queue_number) }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('patients.show', $a->patient) }}" wire:navigate class="block truncate font-semibold text-ink hover:text-primary-700">{{ $a->patient->name }}</a>
                            <p class="text-xs text-muted">
                                {{ $a->doctor->displayName() }} · {{ $a->visitType?->name() }}
                                @if ($a->source === App\Enums\AppointmentSource::WalkIn) · <span class="font-semibold text-orange-700">{{ __('Walk-in') }}</span> @endif
                            </p>
                        </div>
                        <span class="text-xs text-muted" title="{{ __('Waiting time') }}"><x-icon name="clock" class="inline size-4" /> {{ trans_choice(':count min|:count min', $a->waitingMinutes() ?? 0, ['count' => Format::number($a->waitingMinutes() ?? 0)]) }}</span>
                        @if ($canManage)
                            <div class="flex gap-1">
                                @if ($canStartVisit($a))
                                    <form method="post" action="{{ route('visits.start') }}">
                                        @csrf
                                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="stethoscope" class="size-4" /> {{ __('Start visit') }}</button>
                                    </form>
                                @else
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'in_progress')" class="btn btn-secondary btn-sm">{{ __('Call in') }}</button>
                                @endif
                                <button type="button" wire:click="changeStatus({{ $a->id }}, 'no_show')" wire:confirm="{{ __('Mark this patient as a no-show?') }}" class="btn btn-ghost btn-sm text-orange-700">{{ __('Left') }}</button>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-muted">{{ __('The waiting room is empty.') }}</li>
                @endforelse
            </ul>

            @if ($groups['in_room']->isNotEmpty())
                <header class="flex items-center gap-2 border-y border-line bg-slate-50/60 px-5 py-3">
                    <span class="size-2.5 rounded-full bg-violet-500"></span>
                    <h2 class="text-sm font-semibold text-ink">{{ __('With doctor') }}</h2>
                </header>
                <ul class="divide-y divide-line">
                    @foreach ($groups['in_room'] as $a)
                        <li class="flex flex-wrap items-center gap-4 px-5 py-3" wire:key="r-{{ $a->id }}">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-violet-100 font-bold text-violet-800">{{ Format::number($a->queue_number) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $a->patient->name }}</p>
                                <p class="text-xs text-muted">{{ $a->doctor->displayName() }} · {{ __('since :time', ['time' => Format::time($a->started_at)]) }}</p>
                            </div>
                            @if ($canStartVisit($a))
                                <form method="post" action="{{ route('visits.start') }}">
                                    @csrf
                                    <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                    <button type="submit" class="btn btn-primary btn-sm"><x-icon name="stethoscope" class="size-4" /> {{ __('Open visit') }}</button>
                                </form>
                            @endif
                            @if ($canManage)
                                <button type="button" wire:click="changeStatus({{ $a->id }}, 'completed')" class="btn btn-secondary btn-sm"><x-icon name="check" class="size-4" /> {{ __('Complete') }}</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Upcoming today --}}
        <section class="card overflow-hidden">
            <header class="flex items-center gap-2 border-b border-line px-5 py-4">
                <span class="size-2.5 rounded-full bg-blue-500"></span>
                <h2 class="font-semibold text-ink">{{ __('Expected today') }}</h2>
                <x-badge color="blue">{{ Format::number($groups['upcoming']->count()) }}</x-badge>
            </header>
            <ul class="max-h-[32rem] divide-y divide-line overflow-y-auto">
                @forelse ($groups['upcoming'] as $a)
                    <li class="flex items-center gap-3 px-5 py-3" wire:key="u-{{ $a->id }}">
                        <span @class(['w-16 shrink-0 text-sm font-semibold ltr-nums', 'text-rose-600' => $a->starts_at->isPast(), 'text-ink' => ! $a->starts_at->isPast()])>{{ Format::time($a->starts_at) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $a->patient->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $a->doctor->displayName() }}</p>
                        </div>
                        @if ($canManage)
                            <button type="button" wire:click="changeStatus({{ $a->id }}, 'arrived')" class="btn btn-primary btn-sm">{{ __('Check in') }}</button>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">{{ __('No more appointments today.') }}</li>
                @endforelse
            </ul>

            @if ($groups['done']->isNotEmpty())
                <details class="border-t border-line">
                    <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-muted">{{ __('Finished') }} ({{ Format::number($groups['done']->count()) }})</summary>
                    <ul class="divide-y divide-line">
                        @foreach ($groups['done'] as $a)
                            <li class="flex items-center gap-3 px-5 py-2.5 text-sm" wire:key="d-{{ $a->id }}">
                                <span class="min-w-0 flex-1 truncate">{{ $a->patient->name }}</span>
                                @if ($a->status === App\Enums\AppointmentStatus::Completed && $canInvoice)
                                    <a href="{{ route('invoices.create', ['appointment' => $a->id]) }}" wire:navigate class="btn btn-ghost btn-sm text-primary-700"><x-icon name="receipt" class="size-4" /> {{ __('Invoice') }}</a>
                                @endif
                                <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    </div>

    {{-- Walk-in --}}
    <x-modal wire:model="showWalkIn" :title="__('Walk-in patient')" max-width="max-w-lg">
        <form id="walkin-form" wire:submit="registerWalkIn" class="space-y-4">
            <div>
                <p class="form-label">{{ __('Patient') }}</p>
                @if ($walkInPatient)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-primary-200 bg-primary-50/50 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $walkInPatient->name }}</p>
                            <p class="text-xs text-muted ltr-nums">#{{ $walkInPatient->file_number }}</p>
                        </div>
                        <button type="button" wire:click="$set('walkInPatientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                    </div>
                @else
                    <input wire:model.live.debounce.300ms="walkInSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}" @class(['form-control', 'is-invalid' => $errors->has('walkInPatientId')])>
                    @if (mb_strlen($walkInSearch) >= 2)
                        <ul class="mt-2 divide-y divide-line rounded-xl border border-line">
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
