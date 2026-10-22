@php
    use App\Enums\AppointmentSource;
    use App\Enums\AppointmentStatus;
    use App\Support\Format;

    // Split-screen: every appointment of today in one list (grouped by lane), details for the selected one on the other side.
    $lanes = [
        'waiting' => [__('Waiting'), 'bg-amber-400 text-amber-950'],
        'upcoming' => [__('Expected'), 'bg-sky-600 text-white'],
        'in_room' => [__('With doctor'), 'bg-violet-600 text-white'],
        'done' => [__('Finished'), 'bg-slate-300 text-slate-900'],
    ];
    $all = collect($lanes)->keys()->flatMap(fn ($lane) => $groups[$lane]->map(fn ($a) => ['lane' => $lane, 'a' => $a]));
    $firstId = $all->first()['a']->id ?? null;
@endphp
{{-- Tablet-First waiting room: doctor "call next" strip, then a split screen — list on the start side, big-button details on the end side. Polls every 5s. --}}
<div @unless ($showWalkIn) wire:poll.5s @endunless x-data="{ selected: @js($firstId), filter: 'all' }">
    <x-page-header :title="__('Waiting room')" :subtitle="Format::longDate(now())">
        <x-slot:actions>
            @if ($canManage)
                <button type="button" wire:click="openWalkIn" class="btn btn-primary"><x-icon name="user-plus" class="size-6" /> {{ __('Walk-in patient') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Call next strip --}}
    <div class="mb-6 flex gap-3 overflow-x-auto pb-1">
        @foreach ($this->doctors as $doctor)
            @continue($doctorId && $doctorId !== $doctor->id)
            @php
                $inRoom = $groups['in_room']->firstWhere('doctor_id', $doctor->id);
                $waitingCount = $groups['waiting']->where('doctor_id', $doctor->id)->count();
            @endphp
            <div class="flex min-w-[20rem] flex-1 items-center gap-4 rounded-3xl border-2 border-line bg-white p-4" wire:key="doc-{{ $doctor->id }}">
                <span class="grid size-16 shrink-0 place-items-center rounded-2xl text-3xl font-extrabold text-white" style="background: {{ $doctor->doctorProfile?->color }}">{{ $inRoom ? $inRoom->queue_number : '—' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-extrabold">{{ $doctor->displayName() }}</p>
                    <p class="truncate text-[15px] font-semibold text-muted">{{ $inRoom?->patient->name ?? __('Nobody yet') }}</p>
                    <p class="text-[15px] font-bold text-amber-800">{{ trans_choice(':count waiting|:count waiting', $waitingCount, ['count' => Format::number($waitingCount)]) }}</p>
                </div>
                @if ($canManage)
                    <button type="button" wire:click="callNext({{ $doctor->id }})" @disabled($waitingCount === 0) class="btn btn-primary min-h-16 shrink-0 px-5 text-lg">{{ __('Call next') }}</button>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(22rem,30rem)_1fr]">
        {{-- List --}}
        <section class="card flex max-h-[70vh] flex-col overflow-hidden">
            <div class="flex gap-2 overflow-x-auto border-b-2 border-line p-3">
                <button type="button" x-on:click="filter = 'all'" :class="filter === 'all' ? 'bg-ink text-white' : 'bg-slate-100 text-ink'" class="min-h-12 shrink-0 rounded-2xl px-4 text-base font-bold">{{ __('All') }} <span class="opacity-70">{{ $all->count() }}</span></button>
                @foreach ($lanes as $lane => [$label, $tone])
                    <button type="button" x-on:click="filter = '{{ $lane }}'" :class="filter === '{{ $lane }}' ? 'bg-ink text-white' : 'bg-slate-100 text-ink'" class="min-h-12 shrink-0 rounded-2xl px-4 text-base font-bold">{{ $label }} <span class="opacity-70">{{ $groups[$lane]->count() }}</span></button>
                @endforeach
            </div>
            <ul class="flex-1 divide-y-2 divide-line overflow-y-auto">
                @forelse ($all as ['lane' => $lane, 'a' => $a])
                    <li x-show="filter === 'all' || filter === '{{ $lane }}'" wire:key="li-{{ $a->id }}">
                        <button type="button" x-on:click="selected = {{ $a->id }}" :class="selected === {{ $a->id }} ? 'bg-primary-50 ring-4 ring-inset ring-primary-500' : 'hover:bg-slate-50'" class="flex min-h-20 w-full items-center gap-4 px-4 py-3 text-start">
                            <span @class(['grid size-12 shrink-0 place-items-center rounded-2xl font-extrabold', $lanes[$lane][1], 'text-xl' => $a->queue_number, 'text-sm ltr-nums' => ! $a->queue_number])>{{ $a->queue_number ?: $a->starts_at->format('H:i') }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-lg font-bold">{{ $a->patient->name }}</span>
                                <span class="block truncate text-[15px] text-muted"><span class="ltr-nums">{{ $a->starts_at->format('H:i') }}</span> · {{ $a->doctor->displayName() }}</span>
                            </span>
                            @if ($lane === 'waiting')
                                @php $wait = $a->waitingMinutes() ?? 0; @endphp
                                <span @class(['shrink-0 text-base font-extrabold', 'text-rose-700' => $wait >= 30, 'text-amber-800' => $wait < 30])>{{ trans_choice(':count min|:count min', $wait, ['count' => $wait]) }}</span>
                            @endif
                        </button>
                    </li>
                @empty
                    <li class="px-5 py-16 text-center text-lg font-bold text-muted">{{ __('No more appointments today.') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- Details --}}
        <section class="card min-h-[24rem] p-6">
            <div x-show="selected === null" class="grid h-full place-items-center text-center text-xl font-bold text-muted">{{ __('Select a patient from the list.') }}</div>
            @foreach ($all as ['lane' => $lane, 'a' => $a])
                <div x-show="selected === {{ $a->id }}" x-cloak wire:key="detail-{{ $a->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <x-badge :color="$a->status->color()" class="text-base">{{ $a->status->label() }}</x-badge>
                            <h2 class="mt-3 text-4xl leading-tight font-extrabold">{{ $a->patient->name }}</h2>
                            <p class="mt-1 text-lg text-muted">{{ __('File no.') }} <span class="font-bold text-ink ltr-nums">{{ $a->patient->file_number }}</span></p>
                        </div>
                        <span @class(['grid size-24 place-items-center rounded-3xl font-extrabold', $lanes[$lane][1], 'text-5xl' => $a->queue_number, 'text-2xl ltr-nums' => ! $a->queue_number])>{{ $a->queue_number ?: $a->starts_at->format('H:i') }}</span>
                    </div>

                    <dl class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-slate-100 p-4"><dt class="text-[15px] font-bold text-muted">{{ __('Time') }}</dt><dd class="mt-1 text-2xl font-extrabold ltr-nums">{{ $a->starts_at->format('H:i') }}</dd></div>
                        <div class="rounded-2xl bg-slate-100 p-4"><dt class="text-[15px] font-bold text-muted">{{ __('Doctor') }}</dt><dd class="mt-1 text-xl font-extrabold">{{ $a->doctor->displayName() }}</dd></div>
                        <div class="rounded-2xl bg-slate-100 p-4"><dt class="text-[15px] font-bold text-muted">{{ __('Visit type') }}</dt><dd class="mt-1 text-xl font-extrabold">{{ $a->visitType?->name() ?? '—' }}</dd></div>
                        <div class="rounded-2xl bg-slate-100 p-4">
                            <dt class="text-[15px] font-bold text-muted">{{ $lane === 'waiting' ? __('Waiting time') : __('Source') }}</dt>
                            <dd class="mt-1 text-xl font-extrabold">
                                @if ($lane === 'waiting'){{ trans_choice(':count min|:count min', $a->waitingMinutes() ?? 0, ['count' => $a->waitingMinutes() ?? 0]) }}@else{{ $a->source === AppointmentSource::WalkIn ? __('Walk-in') : __('Appointment') }}@endif
                            </dd>
                        </div>
                    </dl>

                    @if ($canManage || $canStartVisit($a))
                        <div class="mt-8 grid gap-3 sm:grid-cols-2">
                            @if ($lane === 'upcoming' && $canManage)
                                <button type="button" wire:click="changeStatus({{ $a->id }}, 'arrived')" class="btn btn-primary btn-lg sm:col-span-2"><x-icon name="check" class="size-7" /> {{ __('Check in') }}</button>
                            @endif
                            @if ($lane === 'waiting')
                                @if ($canStartVisit($a))
                                    <form method="post" action="{{ route('visits.start') }}" class="contents">
                                        @csrf
                                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn btn-primary btn-lg"><x-icon name="stethoscope" class="size-7" /> {{ __('Start visit') }}</button>
                                    </form>
                                @elseif ($canManage)
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'in_progress')" class="btn btn-primary btn-lg">{{ __('Call in') }}</button>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'no_show')" wire:confirm="{{ __('Mark this patient as a no-show?') }}" class="btn btn-secondary btn-lg text-orange-700">{{ __('Left') }}</button>
                                @endif
                            @endif
                            @if ($lane === 'in_room')
                                @if ($canStartVisit($a))
                                    <form method="post" action="{{ route('visits.start') }}" class="contents">
                                        @csrf
                                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                                        <button type="submit" class="btn btn-primary btn-lg">{{ __('Open visit') }}</button>
                                    </form>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="changeStatus({{ $a->id }}, 'completed')" class="btn btn-secondary btn-lg"><x-icon name="check" class="size-7" /> {{ __('Complete') }}</button>
                                @endif
                            @endif
                            @if ($lane === 'done' && $a->status === AppointmentStatus::Completed && $canInvoice)
                                <a href="{{ route('invoices.create', ['appointment' => $a->id]) }}" wire:navigate class="btn btn-primary btn-lg sm:col-span-2"><x-icon name="receipt" class="size-7" /> {{ __('Invoice') }}</a>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('patients.show', $a->patient) }}" wire:navigate class="btn btn-secondary btn-lg"><x-icon name="user" class="size-6" /> {{ __('Patient file') }}</a>
                        @if ($a->patient->phone)
                            <a href="tel:{{ $a->patient->phone }}" class="btn btn-secondary btn-lg"><x-icon name="phone" class="size-6" /> <span class="ltr-nums">{{ $a->patient->phone }}</span></a>
                        @endif
                    </div>
                </div>
            @endforeach
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
                            <p class="text-sm text-muted ltr-nums">#{{ $walkInPatient->file_number }}</p>
                        </div>
                        <button type="button" wire:click="$set('walkInPatientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                    </div>
                @else
                    <input wire:model.live.debounce.300ms="walkInSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}" @class(['form-control', 'is-invalid' => $errors->has('walkInPatientId')])>
                    @if (mb_strlen($walkInSearch) >= 2)
                        <ul class="mt-2 divide-y divide-line rounded-2xl border border-line">
                            @foreach ($this->walkInResults as $result)
                                <li><button type="button" wire:click="$set('walkInPatientId', {{ $result->id }})" class="w-full px-4 py-2.5 text-start text-sm hover:bg-primary-50/60">{{ $result->name }} <span class="text-sm text-muted ltr-nums">#{{ $result->file_number }}</span></button></li>
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
