@php
    use App\Enums\AppointmentStatus;
    use App\Support\Format;

    $ppm = 1.6; // pixels per minute on the time grid
    $height = ($grid['end'] - $grid['start']) * $ppm;
    $y = fn (int $minute) => ($minute - $grid['start']) * $ppm;
    $nowMinute = now()->hour * 60 + now()->minute;
    $hhmm = fn (int $minute) => sprintf('%02d:%02d', intdiv($minute, 60) % 24, $minute % 60);
    $statusStyles = [
        'booked' => 'bg-white',
        'confirmed' => 'bg-blue-50',
        'arrived' => 'bg-amber-50',
        'in_progress' => 'bg-violet-50',
        'completed' => 'bg-emerald-50/70 opacity-80',
        'cancelled' => 'bg-slate-50 opacity-50 line-through',
        'no_show' => 'bg-orange-50 opacity-60',
    ];
@endphp
<div>
    <x-page-header :title="__('Appointments')" :subtitle="$rangeLabel">
        <x-slot:actions>
            @if ($canBook)
                <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $doctorId ?: 'null' }} })" class="btn btn-primary">
                    <x-icon name="plus" class="size-5" /> {{ __('Book appointment') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Toolbar --}}
    <div class="card mb-4 flex flex-col gap-3 p-3 lg:flex-row lg:items-center">
        <div class="flex items-center gap-1">
            <button type="button" wire:click="previous" class="btn btn-ghost p-2" aria-label="{{ __('Previous') }}"><x-icon name="chevron-right" class="size-5 ltr:rotate-180" /></button>
            <button type="button" wire:click="today" @class(['btn btn-sm', 'btn-primary' => $isToday, 'btn-secondary' => ! $isToday])>{{ __('Today') }}</button>
            <button type="button" wire:click="next" class="btn btn-ghost p-2" aria-label="{{ __('Next') }}"><x-icon name="chevron-left" class="size-5 ltr:rotate-180" /></button>
            <input type="date" wire:model.live="date" class="form-control ms-2 w-40 py-1.5">
        </div>

        <div class="flex rounded-lg bg-slate-100 p-1 text-sm font-semibold">
            <button type="button" wire:click="setView('day')" @class(['rounded-md px-3 py-1.5', 'bg-white text-ink shadow-xs' => $view === 'day', 'text-muted' => $view !== 'day'])>{{ __('Day') }}</button>
            <button type="button" wire:click="setView('week')" @class(['rounded-md px-3 py-1.5', 'bg-white text-ink shadow-xs' => $view === 'week', 'text-muted' => $view !== 'week'])>{{ __('Week') }}</button>
        </div>

        @if ($this->doctors->count() > 1)
            <div class="flex flex-wrap items-center gap-1.5 lg:ms-auto">
                @if ($view === 'day')
                    <button type="button" wire:click="selectDoctor(0)" @class(['rounded-full px-3 py-1.5 text-xs font-semibold transition', 'bg-ink text-white' => ! $doctorId, 'bg-slate-100 text-slate-600 hover:bg-slate-200' => $doctorId])>{{ __('All doctors') }}</button>
                @endif
                @foreach ($this->doctors as $doc)
                    <button type="button" wire:click="selectDoctor({{ $doc->id }})" @class([
                        'flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition',
                        'bg-ink text-white' => $doctorId === $doc->id,
                        'bg-slate-100 text-slate-600 hover:bg-slate-200' => $doctorId !== $doc->id,
                    ])>
                        <span class="size-2 rounded-full" style="background: {{ $doc->doctorProfile?->color }}"></span>{{ $doc->displayName() }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (empty($grid['columns']))
        <div class="card px-6 py-14 text-center text-muted">{{ __('No doctors yet. Add a doctor from Users.') }}</div>
    @else
        {{-- Time grid (tablet & desktop) --}}
        <div class="card hidden overflow-hidden md:block" wire:loading.class="opacity-60">
            <div class="overflow-x-auto">
                <div class="min-w-[640px]">
                    {{-- Column headers --}}
                    <div class="flex border-b border-line bg-white">
                        <div class="w-16 shrink-0"></div>
                        @foreach ($grid['columns'] as $column)
                            <div @class(['flex-1 border-s border-line px-3 py-3 text-center', 'bg-primary-50/60' => $column['is_today'] && $view === 'week'])>
                                <p class="flex items-center justify-center gap-2 text-sm font-semibold text-ink">
                                    @if ($view === 'day')<span class="size-2.5 rounded-full" style="background: {{ $column['color'] }}"></span>@endif
                                    {{ $column['label'] }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted">{{ $column['sublabel'] }}
                                    · {{ trans_choice(':count appointment|:count appointments', collect($column['events'])->filter(fn ($e) => $e['appointment']->isActive() || $e['appointment']->status === AppointmentStatus::Completed)->count(), ['count' => collect($column['events'])->filter(fn ($e) => $e['appointment']->isActive() || $e['appointment']->status === AppointmentStatus::Completed)->count()]) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex">
                        {{-- Hour labels --}}
                        <div class="relative w-16 shrink-0" style="height: {{ $height }}px">
                            @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                <span class="absolute inset-x-0 -translate-y-1/2 pe-2 text-end text-[11px] font-medium text-slate-400 ltr-nums" style="top: {{ $y($m) }}px">{{ Format::time(today()->addMinutes($m)) }}</span>
                            @endfor
                        </div>

                        @foreach ($grid['columns'] as $column)
                            <div class="relative flex-1 border-s border-line bg-[repeating-linear-gradient(-45deg,#f8fafc,#f8fafc_6px,#f1f5f9_6px,#f1f5f9_12px)]"
                                 style="height: {{ $height }}px" wire:key="col-{{ $column['key'] }}">
                                {{-- Working windows with clickable slots --}}
                                @foreach ($column['windows'] as $window)
                                    <div class="absolute inset-x-0 bg-white" style="top: {{ $y($window['from']) }}px; height: {{ ($window['to'] - $window['from']) * $ppm }}px"></div>
                                    @if ($canBook)
                                        @for ($m = $window['from']; $m + $column['slot'] <= $window['to']; $m += $column['slot'])
                                            @php $slotStart = \Carbon\CarbonImmutable::parse($column['date'])->addMinutes($m); @endphp
                                            @if ($slotStart->isFuture())
                                                <button type="button"
                                                        x-on:click="$dispatch('book-appointment', { doctorId: {{ $column['doctor_id'] }}, start: '{{ $slotStart->format('Y-m-d H:i') }}' })"
                                                        class="group absolute inset-x-1 flex items-center justify-center rounded-md text-xs font-semibold text-primary-700 opacity-0 transition hover:bg-primary-50 hover:opacity-100"
                                                        style="top: {{ $y($m) }}px; height: {{ $column['slot'] * $ppm }}px" title="{{ $hhmm($m) }}">
                                                    <x-icon name="plus" class="size-3.5" /> <span class="ltr-nums">{{ Format::time($slotStart) }}</span>
                                                </button>
                                            @endif
                                        @endfor
                                    @endif
                                @endforeach

                                {{-- Hour lines --}}
                                @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                    <div class="pointer-events-none absolute inset-x-0 border-t border-line/70" style="top: {{ $y($m) }}px"></div>
                                    <div class="pointer-events-none absolute inset-x-0 border-t border-dashed border-line/40" style="top: {{ $y($m + 30) }}px"></div>
                                @endfor

                                {{-- Blocked periods --}}
                                @foreach ($column['blocked'] as $block)
                                    <div class="absolute inset-x-0 z-[1] flex items-start justify-center bg-rose-50/80 bg-[repeating-linear-gradient(-45deg,transparent,transparent_6px,rgb(254_205_211/0.5)_6px,rgb(254_205_211/0.5)_12px)] pt-2 text-xs font-semibold text-rose-700"
                                         style="top: {{ $y(max($block['from'], $grid['start'])) }}px; height: {{ (min($block['to'], $grid['end']) - max($block['from'], $grid['start'])) * $ppm }}px">
                                        {{ $block['reason'] ?: __('Not available') }}
                                    </div>
                                @endforeach

                                {{-- Appointments --}}
                                @foreach ($column['events'] as $event)
                                    @php
                                        $a = $event['appointment'];
                                        $h = max(22, ($event['to'] - $event['from']) * $ppm - 2);
                                        $color = $a->visitType?->color ?? $column['color'];
                                    @endphp
                                    <button type="button" wire:key="appt-{{ $a->id }}"
                                            x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })"
                                            class="absolute z-[2] overflow-hidden rounded-lg border border-line/80 border-s-4 px-2 py-1 text-start shadow-xs transition hover:z-[3] hover:shadow-(--shadow-lift) {{ $statusStyles[$a->status->value] }}"
                                            style="top: {{ $y($event['from']) + 1 }}px; height: {{ $h }}px; inset-inline-start: calc({{ $event['lane'] }} / {{ $event['lanes'] }} * 100% + 2px); width: calc(100% / {{ $event['lanes'] }} - 4px); border-inline-start-color: {{ $color }}">
                                        <span class="flex items-center gap-1.5 text-xs font-semibold text-ink">
                                            @if ($a->queue_number)<span class="rounded bg-ink px-1 text-[10px] text-white">{{ $a->queue_number }}</span>@endif
                                            <span class="truncate">{{ $a->patient->name }}</span>
                                        </span>
                                        @if ($h > 34)
                                            <span class="block truncate text-[11px] text-muted"><span class="ltr-nums">{{ Format::time($a->starts_at) }}</span> · {{ $a->visitType?->name() }} · {{ $a->status->label() }}</span>
                                        @endif
                                    </button>
                                @endforeach

                                {{-- "Now" line --}}
                                @if ($column['is_today'] && $nowMinute >= $grid['start'] && $nowMinute <= $grid['end'])
                                    <div class="pointer-events-none absolute inset-x-0 z-[4] border-t-2 border-rose-500" style="top: {{ $y($nowMinute) }}px">
                                        <span class="absolute -start-1 -top-1.5 size-3 rounded-full bg-rose-500"></span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Agenda (phones) --}}
        <div class="space-y-4 md:hidden">
            @foreach ($grid['columns'] as $column)
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-4 py-3">
                        <p class="flex items-center gap-2 font-semibold"><span class="size-2.5 rounded-full" style="background: {{ $column['color'] }}"></span>{{ $column['label'] }}</p>
                        <span class="text-xs text-muted">{{ $column['sublabel'] }}</span>
                    </div>
                    @forelse ($column['events'] as $event)
                        @php $a = $event['appointment']; @endphp
                        <button type="button" x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })" class="flex w-full items-center gap-3 border-b border-line px-4 py-3 text-start last:border-0">
                            <span class="w-16 shrink-0 text-sm font-semibold text-ink ltr-nums">{{ Format::time($a->starts_at) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $a->patient->name }}</span>
                                <span class="block truncate text-xs text-muted">{{ $a->visitType?->name() }}</span>
                            </span>
                            <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                        </button>
                    @empty
                        <p class="px-4 py-6 text-center text-sm text-muted">{{ __('No appointments.') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif

    <livewire:appointments.booking-form />
    <livewire:appointments.appointment-details />
    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
