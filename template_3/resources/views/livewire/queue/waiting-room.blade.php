@php
    use App\Enums\AppointmentSource;
    use App\Enums\AppointmentStatus;
    use App\Support\Format;

    // Waiting-time mood: calm under 15 min, meh under 30, worried after that.
    $mood = fn (int $min) => $min < 15 ? ['🙂', 'bg-emerald-50 text-emerald-800'] : ($min < 30 ? ['😐', 'bg-amber-50 text-amber-800'] : ['😟', 'bg-rose-50 text-rose-700']);
@endphp
{{-- Warm & Friendly waiting room: doctor "rooms" with a big now-serving bubble, ticket cards for waiting patients. Polls every 5s. --}}
<div @unless ($showWalkIn) wire:poll.5s @endunless>
    <x-page-header :title="__('Waiting room')" :subtitle="Format::longDate(now())" emoji="queue">
        <x-slot:actions>
            @if ($canManage)
                <button type="button" wire:click="openWalkIn" class="btn btn-primary"><x-emoji name="walk-in" /> {{ __('Walk-in patient') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Doctor rooms --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->doctors as $doctor)
            @continue($doctorId && $doctorId !== $doctor->id)
            @php
                $inRoom = $groups['in_room']->firstWhere('doctor_id', $doctor->id);
                $waitingCount = $groups['waiting']->where('doctor_id', $doctor->id)->count();
                $color = $doctor->doctorProfile?->color;
            @endphp
            <div class="card flex items-center gap-4 p-5" wire:key="doc-{{ $doctor->id }}">
                <div class="grid size-20 shrink-0 place-content-center rounded-full text-center text-white shadow-[0_4px_0_rgb(0_0_0/0.12)]" style="background: {{ $color }}">
                    <span class="text-3xl leading-none font-extrabold">{{ $inRoom ? Format::number($inRoom->queue_number) : '–' }}</span>
                    <span class="text-[10px] font-bold opacity-85">{{ __('Now') }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-extrabold">{{ $doctor->displayName() }}</p>
                    <p class="truncate text-sm text-muted">
                        @if ($inRoom)
                            <x-emoji name="stethoscope" /> <a href="{{ route('patients.show', $inRoom->patient) }}" wire:navigate class="font-bold text-ink hover:text-primary-600">{{ $inRoom->patient->name }}</a>
                        @else
                            <x-emoji name="coffee" /> {{ __('Nobody yet') }}
                        @endif
                    </p>
                    <p class="mt-1 text-sm font-bold text-amber-700"><x-emoji name="queue" /> {{ trans_choice(':count waiting|:count waiting', $waitingCount, ['count' => Format::number($waitingCount)]) }}</p>
                </div>
                @if ($canManage)
                    <button type="button" wire:click="callNext({{ $doctor->id }})" @disabled($waitingCount === 0) class="btn btn-primary btn-sm shrink-0 flex-col gap-0 rounded-3xl px-3 py-2">
                        <x-emoji name="bell" class="text-xl" /> {{ __('Call next') }}
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Waiting tickets --}}
            <section>
                <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold"><x-emoji name="queue" /> {{ __('Waiting') }} <span class="rounded-full bg-amber-100 px-3 text-base text-amber-800">{{ Format::number($groups['waiting']->count()) }}</span></h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($groups['waiting'] as $a)
                        @php $wait = $a->waitingMinutes() ?? 0; [$face, $tone] = $mood($wait); @endphp
                        <div class="card relative overflow-hidden p-4" wire:key="w-{{ $a->id }}">
                            {{-- ticket notches --}}
                            <span class="absolute -start-3 top-1/2 size-6 -translate-y-1/2 rounded-full bg-canvas"></span>
                            <span class="absolute -end-3 top-1/2 size-6 -translate-y-1/2 rounded-full bg-canvas"></span>
                            <div class="flex items-center gap-3">
                                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-amber-100 text-2xl font-extrabold text-amber-800">{{ Format::number($a->queue_number) }}</span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('patients.show', $a->patient) }}" wire:navigate class="block truncate text-lg font-extrabold hover:text-primary-600">{{ $a->patient->name }}</a>
                                    <p class="truncate text-sm text-muted">{{ $a->doctor->displayName() }} · {{ $a->visitType?->name() }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-sm font-bold {{ $tone }}" title="{{ __('Waiting time') }}"><span class="emoji">{{ $face }}</span> {{ trans_choice(':count min|:count min', $wait, ['count' => Format::number($wait)]) }}</span>
                            </div>
                            @if ($a->source === AppointmentSource::WalkIn)
                                <p class="mt-2 text-sm font-bold text-orange-700"><x-emoji name="walk-in" /> {{ __('Walk-in') }}</p>
                            @endif
                            @if ($canManage)
                                <div class="mt-3 flex flex-wrap gap-2 border-t-2 border-dashed border-line pt-3">
                                    @if ($canStartVisit($a))
                                        <form method="post" action="{{ route('visits.start') }}">
                                            @csrf
                                            <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                            <button type="submit" class="btn btn-primary btn-sm"><x-emoji name="stethoscope" /> {{ __('Start visit') }}</button>
                                        </form>
                                    @else
                                        <button type="button" wire:click="changeStatus({{ $a->id }}, 'in_progress')" class="btn btn-secondary btn-sm">{{ __('Call in') }}</button>
                                    @endif
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'no_show')" wire:confirm="{{ __('Mark this patient as a no-show?') }}" class="btn btn-ghost btn-sm text-orange-700">{{ __('Left') }}</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="card p-10 text-center sm:col-span-2"><span class="emoji text-5xl">🪴</span><p class="mt-2 font-bold text-muted">{{ __('The waiting room is empty.') }}</p></div>
                    @endforelse
                </div>
            </section>

            {{-- With the doctor --}}
            @if ($groups['in_room']->isNotEmpty())
                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold"><x-emoji name="stethoscope" /> {{ __('With doctor') }}</h2>
                    <div class="space-y-2">
                        @foreach ($groups['in_room'] as $a)
                            <div class="card flex flex-wrap items-center gap-3 p-3" wire:key="r-{{ $a->id }}">
                                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-violet-100 font-extrabold text-violet-800">{{ Format::number($a->queue_number) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-bold">{{ $a->patient->name }}</p>
                                    <p class="text-sm text-muted">{{ $a->doctor->displayName() }} · {{ __('since :time', ['time' => Format::time($a->started_at)]) }}</p>
                                </div>
                                @if ($canStartVisit($a))
                                    <form method="post" action="{{ route('visits.start') }}">
                                        @csrf
                                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Open visit') }}</button>
                                    </form>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'completed')" class="btn btn-secondary btn-sm"><x-emoji name="success" /> {{ __('Complete') }}</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Expected today --}}
        <section class="card self-start p-5">
            <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold"><x-emoji name="calendar" /> {{ __('Expected today') }} <span class="rounded-full bg-sky-100 px-3 text-base text-sky-800">{{ Format::number($groups['upcoming']->count()) }}</span></h2>
            <ul class="max-h-[34rem] space-y-2 overflow-y-auto">
                @forelse ($groups['upcoming'] as $a)
                    <li class="flex items-center gap-3 rounded-2xl bg-canvas p-2.5" wire:key="u-{{ $a->id }}">
                        <span @class(['w-14 shrink-0 rounded-xl bg-white py-1 text-center text-sm font-extrabold ltr-nums', 'text-rose-600' => $a->starts_at->isPast()])>{{ Format::time($a->starts_at) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold">{{ $a->patient->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $a->doctor->displayName() }}</p>
                        </div>
                        @if ($canManage)
                            <button type="button" wire:click="changeStatus({{ $a->id }}, 'arrived')" class="btn btn-primary btn-sm">{{ __('Check in') }}</button>
                        @endif
                    </li>
                @empty
                    <li class="py-8 text-center"><span class="emoji text-4xl">☕</span><p class="mt-2 font-bold text-muted">{{ __('No more appointments today.') }}</p></li>
                @endforelse
            </ul>

            @if ($groups['done']->isNotEmpty())
                <details class="mt-4 rounded-2xl bg-canvas">
                    <summary class="cursor-pointer px-4 py-3 font-bold text-muted"><span class="emoji">✅</span> {{ __('Finished') }} ({{ Format::number($groups['done']->count()) }})</summary>
                    <ul class="space-y-1 px-2 pb-2">
                        @foreach ($groups['done'] as $a)
                            <li class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm" wire:key="d-{{ $a->id }}">
                                <span class="min-w-0 flex-1 truncate font-semibold">{{ $a->patient->name }}</span>
                                @if ($a->status === AppointmentStatus::Completed && $canInvoice)
                                    <a href="{{ route('invoices.create', ['appointment' => $a->id]) }}" wire:navigate class="text-primary-600" title="{{ __('Invoice') }}"><x-emoji name="invoice" /></a>
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
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-primary-200 bg-primary-50/50 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $walkInPatient->name }}</p>
                            <p class="text-xs text-muted ltr-nums">#{{ $walkInPatient->file_number }}</p>
                        </div>
                        <button type="button" wire:click="$set('walkInPatientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                    </div>
                @else
                    <input wire:model.live.debounce.300ms="walkInSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}" @class(['form-control', 'is-invalid' => $errors->has('walkInPatientId')])>
                    @if (mb_strlen($walkInSearch) >= 2)
                        <ul class="mt-2 divide-y divide-line rounded-2xl border border-line">
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
