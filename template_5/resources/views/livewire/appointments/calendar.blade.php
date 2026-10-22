@php
    use App\Enums\AppointmentStatus;
    use Carbon\CarbonImmutable;

    /*
     * Tablet-First calendar: a horizontal timeline. Each doctor (day view) or day (week view) is a tall row,
     * time runs along the reading direction, appointments are big tappable blocks and every free slot is a
     * full-height "+" target. Swipe sideways to move through the day.
     */
    $ppm = 6; // pixels per minute (horizontal) — 15 minutes = 90px, a comfortable touch target
    $width = ($grid['end'] - $grid['start']) * $ppm;
    $x = fn (int $minute) => ($minute - $grid['start']) * $ppm;
    $rowHeight = 104;
    $hhmm = fn (int $m) => sprintf('%02d:%02d', intdiv($m, 60) % 24, $m % 60);
    $nowMinute = now()->hour * 60 + now()->minute;
    $statusStyles = [
        'booked' => 'bg-white border-slate-400 text-ink',
        'confirmed' => 'bg-primary-600 border-primary-800 text-white',
        'arrived' => 'bg-amber-400 border-amber-600 text-amber-950',
        'in_progress' => 'bg-violet-600 border-violet-800 text-white',
        'completed' => 'bg-emerald-100 border-emerald-500 text-emerald-950',
        'cancelled' => 'bg-slate-100 border-slate-300 text-slate-500 line-through',
        'no_show' => 'bg-orange-100 border-orange-400 text-orange-900',
    ];
@endphp
<div>
    <x-page-header :title="__('Appointments')" :subtitle="$rangeLabel">
        <x-slot:actions>
            @if ($canBook)
                <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $doctorId ?: 'null' }} })" class="btn btn-primary"><x-icon name="plus" class="size-6" /> {{ __('Book appointment') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Big controls --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previous" class="grid size-14 place-items-center rounded-2xl border-2 border-line bg-white" aria-label="{{ __('Previous') }}"><x-icon name="chevron-right" class="size-7 ltr:rotate-180" /></button>
            <button type="button" wire:click="today" @class(['btn min-h-14 px-6 text-lg', 'btn-primary' => $isToday, 'btn-secondary' => ! $isToday])>{{ __('Today') }}</button>
            <button type="button" wire:click="next" class="grid size-14 place-items-center rounded-2xl border-2 border-line bg-white" aria-label="{{ __('Next') }}"><x-icon name="chevron-left" class="size-7 ltr:rotate-180" /></button>
            <input type="date" wire:model.live="date" class="form-control min-h-14 w-48 text-lg">
        </div>
        <div class="flex rounded-2xl border-2 border-line bg-white p-1">
            @foreach (['day' => __('Day'), 'week' => __('Week')] as $key => $label)
                <button type="button" wire:click="setView('{{ $key }}')" @class(['min-h-12 rounded-xl px-6 text-lg font-bold', 'bg-ink text-white' => $view === $key, 'text-ink' => $view !== $key])>{{ $label }}</button>
            @endforeach
        </div>
        @if ($this->doctors->count() > 1)
            <div class="flex flex-wrap gap-2 lg:ms-auto">
                @if ($view === 'day')
                    <button type="button" wire:click="selectDoctor(0)" @class(['min-h-12 rounded-2xl border-2 px-5 text-base font-bold', 'border-ink bg-ink text-white' => ! $doctorId, 'border-line bg-white' => $doctorId])>{{ __('All doctors') }}</button>
                @endif
                @foreach ($this->doctors as $doc)
                    <button type="button" wire:click="selectDoctor({{ $doc->id }})" @class(['flex min-h-12 items-center gap-2 rounded-2xl border-2 px-5 text-base font-bold', 'border-ink bg-ink text-white' => $doctorId === $doc->id, 'border-line bg-white' => $doctorId !== $doc->id])>
                        <span class="size-4 rounded" style="background: {{ $doc->doctorProfile?->color }}"></span>{{ $doc->displayName() }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (empty($grid['columns']))
        <div class="card px-5 py-16 text-center text-xl font-bold text-muted">{{ __('No doctors yet. Add a doctor from Users.') }}</div>
    @else
        <div class="card overflow-hidden" wire:loading.class="opacity-60">
            <div class="flex">
                {{-- Row labels (fixed) --}}
                <div class="w-44 shrink-0 border-e-2 border-line bg-slate-50">
                    <div class="h-12 border-b-2 border-line"></div>
                    @foreach ($grid['columns'] as $column)
                        <div @class(['flex flex-col justify-center gap-0.5 border-b-2 border-line px-4', 'bg-primary-50' => $column['is_today'] && $view === 'week']) style="height: {{ $rowHeight }}px">
                            <p class="flex items-center gap-2 truncate text-lg font-extrabold"><span class="size-3 shrink-0 rounded" style="background: {{ $column['color'] }}"></span>{{ $column['label'] }}</p>
                            <p class="truncate text-sm font-semibold text-muted">{{ $column['sublabel'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Scrollable timeline --}}
                <div class="flex-1 overflow-x-auto" x-data x-init="$nextTick(() => { const n = $el.querySelector('[data-now]'); if (n) n.scrollIntoView({ inline: 'center', block: 'nearest' }) })">
                    <div class="relative" style="width: {{ $width }}px">
                        {{-- Hour ruler --}}
                        <div class="relative h-12 border-b-2 border-line bg-slate-50">
                            @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                <span class="absolute top-0 flex h-full items-center border-s-2 border-line ps-2 text-base font-extrabold text-ink" style="inset-inline-start: {{ $x($m) }}px"><span class="ltr-nums">{{ $hhmm($m) }}</span></span>
                            @endfor
                        </div>

                        @foreach ($grid['columns'] as $column)
                            <div class="relative border-b-2 border-line bg-[repeating-linear-gradient(45deg,#f1f2f8,#f1f2f8_8px,#e7e9f3_8px,#e7e9f3_16px)]" style="height: {{ $rowHeight }}px" wire:key="row-{{ $column['key'] }}">
                                @foreach ($column['windows'] as $window)
                                    <div class="absolute inset-y-0 bg-white" style="inset-inline-start: {{ $x($window['from']) }}px; width: {{ ($window['to'] - $window['from']) * $ppm }}px"></div>
                                    @if ($canBook)
                                        @for ($m = $window['from']; $m + $column['slot'] <= $window['to']; $m += $column['slot'])
                                            @php $slotStart = CarbonImmutable::parse($column['date'])->addMinutes($m); @endphp
                                            @if ($slotStart->isFuture())
                                                <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $column['doctor_id'] }}, start: '{{ $slotStart->format('Y-m-d H:i') }}' })"
                                                        class="absolute inset-y-1 grid place-items-center rounded-xl border-2 border-dashed border-transparent text-primary-700 transition hover:border-primary-400 hover:bg-primary-50 active:bg-primary-100"
                                                        style="inset-inline-start: {{ $x($m) + 1 }}px; width: {{ $column['slot'] * $ppm - 2 }}px" aria-label="{{ __('Book appointment') }} {{ $hhmm($m) }}">
                                                    <x-icon name="plus" class="size-6 opacity-30" />
                                                </button>
                                            @endif
                                        @endfor
                                    @endif
                                @endforeach

                                @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                    <div class="pointer-events-none absolute inset-y-0 border-s-2 border-line/70" style="inset-inline-start: {{ $x($m) }}px"></div>
                                @endfor

                                @foreach ($column['blocked'] as $block)
                                    <div class="absolute inset-y-0 z-[1] flex items-center justify-center bg-rose-100/90 px-2 text-base font-bold text-rose-800"
                                         style="inset-inline-start: {{ $x(max($block['from'], $grid['start'])) }}px; width: {{ (min($block['to'], $grid['end']) - max($block['from'], $grid['start'])) * $ppm }}px">
                                        <span class="truncate">{{ $block['reason'] ?: __('Not available') }}</span>
                                    </div>
                                @endforeach

                                @foreach ($column['events'] as $event)
                                    @php
                                        $a = $event['appointment'];
                                        $laneHeight = ($rowHeight - 8) / $event['lanes'];
                                    @endphp
                                    <button type="button" wire:key="appt-{{ $a->id }}" x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })"
                                            class="absolute z-[2] flex flex-col justify-center overflow-hidden rounded-xl border-2 border-s-[6px] px-2.5 text-start transition active:scale-[0.98] {{ $statusStyles[$a->status->value] }}"
                                            style="inset-inline-start: {{ $x($event['from']) + 2 }}px; width: {{ max(44, ($event['to'] - $event['from']) * $ppm - 4) }}px; top: {{ 4 + $event['lane'] * $laneHeight }}px; height: {{ $laneHeight - 4 }}px">
                                        <span class="truncate text-[15px] leading-tight font-extrabold">{{ $a->patient->name }}</span>
                                        @if ($laneHeight > 60)
                                            <span class="truncate text-sm font-semibold opacity-80"><span class="ltr-nums">{{ $a->starts_at->format('H:i') }}</span> · {{ $a->status->label() }}</span>
                                        @endif
                                    </button>
                                @endforeach

                                @if ($column['is_today'] && $nowMinute >= $grid['start'] && $nowMinute <= $grid['end'])
                                    <div data-now class="pointer-events-none absolute inset-y-0 z-[4] w-1 rounded-full bg-rose-600" style="inset-inline-start: {{ $x($nowMinute) }}px"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach (AppointmentStatus::cases() as $status)
                <span class="rounded-xl border-2 border-s-[6px] px-3 py-1.5 text-sm font-bold {{ $statusStyles[$status->value] }}">{{ $status->label() }}</span>
            @endforeach
        </div>
    @endif

    <livewire:appointments.booking-form />
    <livewire:appointments.appointment-details />
    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
