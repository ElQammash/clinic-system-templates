@php
    use App\Enums\AppointmentSource;
    use App\Enums\AppointmentStatus;
    use App\Support\Format;
@endphp
{{-- Dark Pro queue board: "now serving" strip per doctor + three lanes (expected → waiting → with doctor). Polls every 5s. --}}
<div @unless ($showWalkIn) wire:poll.5s @endunless>
    <x-page-header :title="__('Waiting room')" :subtitle="Format::longDate(now())">
        <x-slot:actions>
            <span class="hidden items-center gap-1.5 text-xs text-muted sm:flex"><span class="size-1.5 animate-pulse rounded-full bg-primary-400"></span>{{ __('Live') }}</span>
            @if ($canManage)
                <button type="button" wire:click="openWalkIn" class="btn btn-primary btn-sm"><x-icon name="user-plus" class="size-4" /> {{ __('Walk-in patient') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Now serving --}}
    <div class="mb-4 grid grid-cols-[repeat(auto-fit,minmax(17rem,1fr))] gap-px overflow-hidden rounded-(--radius-card) border border-line bg-line">
        @foreach ($this->doctors as $doctor)
            @continue($doctorId && $doctorId !== $doctor->id)
            @php
                $inRoom = $groups['in_room']->firstWhere('doctor_id', $doctor->id);
                $waitingCount = $groups['waiting']->where('doctor_id', $doctor->id)->count();
            @endphp
            <div class="flex items-center gap-3 bg-surface p-3" wire:key="doc-{{ $doctor->id }}">
                <div class="grid size-12 shrink-0 place-items-center rounded-sm border font-mono text-xl font-bold"
                     style="border-color: {{ $doctor->doctorProfile?->color }}; color: {{ $doctor->doctorProfile?->color }}">
                    {{ $inRoom ? $inRoom->queue_number : '—' }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-ink">{{ $doctor->displayName() }}</p>
                    <p class="truncate text-[11px] text-muted">
                        @if ($inRoom)
                            <a href="{{ route('patients.show', $inRoom->patient) }}" wire:navigate class="text-violet-500 hover:underline">{{ $inRoom->patient->name }}</a>
                        @else
                            {{ __('Nobody yet') }}
                        @endif
                        · <span class="text-amber-500">{{ trans_choice(':count waiting|:count waiting', $waitingCount, ['count' => Format::number($waitingCount)]) }}</span>
                    </p>
                </div>
                @if ($canManage)
                    <button type="button" wire:click="callNext({{ $doctor->id }})" @disabled($waitingCount === 0) class="btn btn-primary btn-sm shrink-0" title="{{ __('Call next') }}">
                        <x-icon name="sparkles" class="size-3.5" /> <span class="hidden 2xl:inline">{{ __('Call next') }}</span>
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Lanes --}}
    <div class="grid gap-3 lg:grid-cols-3">
        {{-- Expected --}}
        <section class="card flex flex-col overflow-hidden">
            <header class="flex items-center gap-2 border-b border-line border-t-2 border-t-sky-500 bg-subtle px-3 py-2">
                <h2 class="text-xs font-semibold tracking-wider text-ink uppercase">{{ __('Expected today') }}</h2>
                <span class="ms-auto font-mono text-xs text-sky-500">{{ $groups['upcoming']->count() }}</span>
            </header>
            <ul class="max-h-[34rem] flex-1 divide-y divide-line overflow-y-auto">
                @forelse ($groups['upcoming'] as $a)
                    <li class="flex items-center gap-2.5 px-3 py-2" wire:key="u-{{ $a->id }}">
                        <span @class(['w-11 shrink-0 font-mono text-xs', 'text-rose-500' => $a->starts_at->isPast(), 'text-ink' => ! $a->starts_at->isPast()]) dir="ltr">{{ $a->starts_at->format('H:i') }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] text-ink">{{ $a->patient->name }}</p>
                            <p class="truncate text-[11px] text-muted">{{ $a->doctor->displayName() }}</p>
                        </div>
                        @if ($canManage)
                            <button type="button" wire:click="changeStatus({{ $a->id }}, 'arrived')" class="btn btn-secondary btn-sm">{{ __('Check in') }}</button>
                        @endif
                    </li>
                @empty
                    <li class="px-3 py-10 text-center text-xs text-muted">{{ __('No more appointments today.') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- Waiting --}}
        <section class="card flex flex-col overflow-hidden">
            <header class="flex items-center gap-2 border-b border-line border-t-2 border-t-amber-500 bg-subtle px-3 py-2">
                <h2 class="text-xs font-semibold tracking-wider text-ink uppercase">{{ __('Waiting') }}</h2>
                <span class="ms-auto font-mono text-xs text-amber-500">{{ $groups['waiting']->count() }}</span>
            </header>
            <ul class="max-h-[34rem] flex-1 divide-y divide-line overflow-y-auto">
                @forelse ($groups['waiting'] as $a)
                    @php $wait = $a->waitingMinutes() ?? 0; @endphp
                    <li class="px-3 py-2.5" wire:key="w-{{ $a->id }}">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-8 shrink-0 place-items-center rounded-sm bg-amber-500/15 font-mono text-sm font-bold text-amber-500">{{ $a->queue_number }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('patients.show', $a->patient) }}" wire:navigate class="block truncate text-[13px] font-medium text-ink hover:text-primary-400">{{ $a->patient->name }}</a>
                                <p class="truncate text-[11px] text-muted">
                                    {{ $a->doctor->displayName() }} · {{ $a->visitType?->name() }}
                                    @if ($a->source === AppointmentSource::WalkIn) · <span class="text-orange-500">{{ __('Walk-in') }}</span> @endif
                                </p>
                            </div>
                            <span @class(['font-mono text-[11px]', 'text-rose-500' => $wait >= 30, 'text-muted' => $wait < 30]) title="{{ __('Waiting time') }}">{{ trans_choice(':count min|:count min', $wait, ['count' => $wait]) }}</span>
                        </div>
                        @if ($canManage)
                            <div class="mt-2 flex gap-1 ps-[2.625rem]">
                                @if ($canStartVisit($a))
                                    <form method="post" action="{{ route('visits.start') }}">
                                        @csrf
                                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="stethoscope" class="size-3.5" /> {{ __('Start visit') }}</button>
                                    </form>
                                @else
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'in_progress')" class="btn btn-secondary btn-sm">{{ __('Call in') }}</button>
                                @endif
                                <button type="button" wire:click="changeStatus({{ $a->id }}, 'no_show')" wire:confirm="{{ __('Mark this patient as a no-show?') }}" class="btn btn-ghost btn-sm text-orange-500">{{ __('Left') }}</button>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="px-3 py-10 text-center text-xs text-muted">{{ __('The waiting room is empty.') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- With doctor + finished --}}
        <section class="card flex flex-col overflow-hidden">
            <header class="flex items-center gap-2 border-b border-line border-t-2 border-t-violet-500 bg-subtle px-3 py-2">
                <h2 class="text-xs font-semibold tracking-wider text-ink uppercase">{{ __('With doctor') }}</h2>
                <span class="ms-auto font-mono text-xs text-violet-500">{{ $groups['in_room']->count() }}</span>
            </header>
            <ul class="divide-y divide-line">
                @forelse ($groups['in_room'] as $a)
                    <li class="flex flex-wrap items-center gap-2.5 px-3 py-2.5" wire:key="r-{{ $a->id }}">
                        <span class="grid size-8 shrink-0 place-items-center rounded-sm bg-violet-500/15 font-mono text-sm font-bold text-violet-500">{{ $a->queue_number }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $a->patient->name }}</p>
                            <p class="truncate text-[11px] text-muted">{{ $a->doctor->displayName() }} · {{ __('since :time', ['time' => Format::time($a->started_at)]) }}</p>
                        </div>
                        <div class="flex gap-1">
                            @if ($canStartVisit($a))
                                <form method="post" action="{{ route('visits.start') }}">
                                    @csrf
                                    <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                    <button type="submit" class="btn btn-primary btn-sm" title="{{ __('Open visit') }}"><x-icon name="stethoscope" class="size-3.5" /></button>
                                </form>
                            @endif
                            @if ($canManage)
                                <button type="button" wire:click="changeStatus({{ $a->id }}, 'completed')" class="btn btn-secondary btn-sm" title="{{ __('Complete') }}"><x-icon name="check" class="size-3.5" /> {{ __('Complete') }}</button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-3 py-8 text-center text-xs text-muted">{{ __('Nobody yet') }}</li>
                @endforelse
            </ul>

            @if ($groups['done']->isNotEmpty())
                <div class="mt-auto border-t border-line">
                    <p class="flex items-center gap-2 bg-subtle px-3 py-2 text-xs font-semibold tracking-wider text-muted uppercase">
                        {{ __('Finished') }} <span class="ms-auto font-mono">{{ $groups['done']->count() }}</span>
                    </p>
                    <ul class="max-h-60 divide-y divide-line overflow-y-auto">
                        @foreach ($groups['done'] as $a)
                            <li class="flex items-center gap-2 px-3 py-1.5 text-[13px]" wire:key="d-{{ $a->id }}">
                                <span class="min-w-0 flex-1 truncate text-ink-soft">{{ $a->patient->name }}</span>
                                @if ($a->status === AppointmentStatus::Completed && $canInvoice)
                                    <a href="{{ route('invoices.create', ['appointment' => $a->id]) }}" wire:navigate class="text-primary-400 hover:text-primary-300" title="{{ __('Invoice') }}"><x-icon name="receipt" class="size-3.5" /></a>
                                @endif
                                <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    </div>

    {{-- Walk-in --}}
    <x-modal wire:model="showWalkIn" :title="__('Walk-in patient')" max-width="max-w-lg">
        <form id="walkin-form" wire:submit="registerWalkIn" class="space-y-4">
            <div>
                <p class="form-label">{{ __('Patient') }}</p>
                @if ($walkInPatient)
                    <div class="flex items-center justify-between gap-3 rounded border border-primary-500/40 bg-primary-500/10 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $walkInPatient->name }}</p>
                            <p class="text-xs text-muted ltr-nums">#{{ $walkInPatient->file_number }}</p>
                        </div>
                        <button type="button" wire:click="$set('walkInPatientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                    </div>
                @else
                    <input wire:model.live.debounce.300ms="walkInSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}" @class(['form-control', 'is-invalid' => $errors->has('walkInPatientId')])>
                    @if (mb_strlen($walkInSearch) >= 2)
                        <ul class="mt-2 divide-y divide-line rounded border border-line">
                            @foreach ($this->walkInResults as $result)
                                <li><button type="button" wire:click="$set('walkInPatientId', {{ $result->id }})" class="w-full px-4 py-2.5 text-start text-sm hover:bg-subtle">{{ $result->name }} <span class="text-xs text-muted ltr-nums">#{{ $result->file_number }}</span></button></li>
                            @endforeach
                            @can('create', App\Models\Patient::class)
                                <li><button type="button" wire:click="newWalkInPatient" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm font-semibold text-primary-400 hover:bg-subtle"><x-icon name="user-plus" class="size-4" /> {{ __('New patient') }}</button></li>
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
