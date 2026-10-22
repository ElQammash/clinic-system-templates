@php
    use App\Enums\AppointmentStatus;
    use App\Support\Format;

    // Dark Pro: compact grid (1.2px per minute), doctors as resource columns, flat tinted chips.
    $ppm = 1.2;
    $height = ($grid['end'] - $grid['start']) * $ppm;
    $y = fn (int $minute) => ($minute - $grid['start']) * $ppm;
    $nowMinute = now()->hour * 60 + now()->minute;
    $hhmm = fn (int $minute) => sprintf('%02d:%02d', intdiv($minute, 60) % 24, $minute % 60);
    $statusStyles = [
        'booked' => '',
        'confirmed' => '',
        'arrived' => 'ring-1 ring-amber-500/60',
        'in_progress' => 'ring-1 ring-violet-500/70',
        'completed' => 'opacity-55',
        'cancelled' => 'opacity-35 line-through',
        'no_show' => 'opacity-40',
    ];
    $statusDot = [
        'booked' => 'bg-slate-400', 'confirmed' => 'bg-sky-500', 'arrived' => 'bg-amber-500', 'in_progress' => 'bg-violet-500',
        'completed' => 'bg-emerald-500', 'cancelled' => 'bg-slate-500', 'no_show' => 'bg-orange-500',
    ];
    $activeCount = fn ($column) => collect($column['events'])->filter(fn ($e) => $e['appointment']->isActive() || $e['appointment']->status === AppointmentStatus::Completed)->count();
@endphp
<div>
    <x-page-header :title="__('Appointments')" :subtitle="$rangeLabel">
        <x-slot:actions>
            @if ($canBook)
                <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $doctorId ?: 'null' }} })" class="btn btn-primary btn-sm">
                    <x-icon name="plus" class="size-4" /> {{ __('Book appointment') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Toolbar: one dense strip --}}
    <div class="mb-3 flex flex-col gap-2 lg:flex-row lg:items-center">
        <div class="flex items-center overflow-hidden rounded-sm border border-line bg-surface">
            <button type="button" wire:click="previous" class="grid size-8 place-items-center text-muted hover:bg-subtle hover:text-ink" aria-label="{{ __('Previous') }}"><x-icon name="chevron-right" class="size-4 ltr:rotate-180" /></button>
            <button type="button" wire:click="today" @class(['h-8 border-x border-line px-3 text-xs font-semibold', 'bg-primary-500 text-slate-950' => $isToday, 'text-ink hover:bg-subtle' => ! $isToday])>{{ __('Today') }}</button>
            <button type="button" wire:click="next" class="grid size-8 place-items-center text-muted hover:bg-subtle hover:text-ink" aria-label="{{ __('Next') }}"><x-icon name="chevron-left" class="size-4 ltr:rotate-180" /></button>
            <input type="date" wire:model.live="date" class="h-8 w-36 border-0 border-s border-line bg-transparent px-2 font-mono text-xs text-ink focus:ring-0">
        </div>

        <div class="flex overflow-hidden rounded-sm border border-line bg-surface text-xs font-semibold">
            @foreach (['day' => __('Day'), 'week' => __('Week')] as $key => $label)
                <button type="button" wire:click="setView('{{ $key }}')" @class(['h-8 px-3', 'bg-subtle text-primary-400' => $view === $key, 'text-muted hover:text-ink' => $view !== $key])>{{ $label }}</button>
            @endforeach
        </div>

        @if ($this->doctors->count() > 1)
            <div class="flex flex-wrap items-center gap-1 lg:ms-auto">
                @if ($view === 'day')
                    <button type="button" wire:click="selectDoctor(0)" @class(['h-7 rounded-sm border px-2.5 text-xs font-semibold transition', 'border-primary-500 bg-primary-500/10 text-primary-400' => ! $doctorId, 'border-line text-muted hover:text-ink' => $doctorId])>{{ __('All doctors') }}</button>
                @endif
                @foreach ($this->doctors as $doc)
                    <button type="button" wire:click="selectDoctor({{ $doc->id }})" @class([
                        'flex h-7 items-center gap-1.5 rounded-sm border px-2.5 text-xs font-semibold transition',
                        'border-primary-500 bg-primary-500/10 text-primary-400' => $doctorId === $doc->id,
                        'border-line text-muted hover:text-ink' => $doctorId !== $doc->id,
                    ])>
                        <span class="size-2 rounded-[1px]" style="background: {{ $doc->doctorProfile?->color }}"></span>{{ $doc->displayName() }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (empty($grid['columns']))
        <div class="card px-5 py-14 text-center text-xs text-muted">{{ __('No doctors yet. Add a doctor from Users.') }}</div>
    @else
        {{-- Resource grid (tablet & desktop) --}}
        <div class="card hidden overflow-hidden md:block" wire:loading.class="opacity-60">
            <div class="overflow-x-auto">
                <div class="min-w-[640px]">
                    <div class="flex border-b border-line bg-subtle">
                        <div class="w-14 shrink-0"></div>
                        @foreach ($grid['columns'] as $column)
                            <div @class(['flex flex-1 items-center justify-between gap-2 border-s border-line px-2.5 py-2', 'bg-primary-500/10' => $column['is_today'] && $view === 'week'])>
                                <p class="flex min-w-0 items-center gap-1.5 text-xs font-semibold text-ink">
                                    <span class="size-2 shrink-0 rounded-[1px]" style="background: {{ $column['color'] }}"></span>
                                    <span class="truncate">{{ $column['label'] }}</span>
                                    <span class="hidden truncate font-normal text-muted xl:inline">{{ $column['sublabel'] }}</span>
                                </p>
                                <span class="rounded-[3px] bg-surface px-1.5 font-mono text-[10px] text-muted">{{ $activeCount($column) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex">
                        <div class="relative w-14 shrink-0 bg-subtle/50" style="height: {{ $height }}px">
                            @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                <span class="absolute inset-x-0 pe-1.5 pt-0.5 text-end font-mono text-[10px] text-muted" style="top: {{ $y($m) }}px" dir="ltr">{{ $hhmm($m) }}</span>
                            @endfor
                        </div>

                        @foreach ($grid['columns'] as $column)
                            <div class="relative flex-1 border-s border-line bg-[repeating-linear-gradient(-45deg,transparent,transparent_5px,var(--color-line)_5px,var(--color-line)_6px)]"
                                 style="height: {{ $height }}px" wire:key="col-{{ $column['key'] }}">
                                @foreach ($column['windows'] as $window)
                                    <div class="absolute inset-x-0 bg-surface" style="top: {{ $y($window['from']) }}px; height: {{ ($window['to'] - $window['from']) * $ppm }}px"></div>
                                    @if ($canBook)
                                        @for ($m = $window['from']; $m + $column['slot'] <= $window['to']; $m += $column['slot'])
                                            @php $slotStart = \Carbon\CarbonImmutable::parse($column['date'])->addMinutes($m); @endphp
                                            @if ($slotStart->isFuture())
                                                <button type="button"
                                                        x-on:click="$dispatch('book-appointment', { doctorId: {{ $column['doctor_id'] }}, start: '{{ $slotStart->format('Y-m-d H:i') }}' })"
                                                        class="absolute inset-x-0.5 flex items-center gap-1 rounded-[2px] border border-dashed border-primary-500/60 px-1.5 font-mono text-[10px] text-primary-400 opacity-0 transition hover:opacity-100"
                                                        style="top: {{ $y($m) }}px; height: {{ $column['slot'] * $ppm }}px" title="{{ $hhmm($m) }}">
                                                    <x-icon name="plus" class="size-3" /> <span dir="ltr">{{ $hhmm($m) }}</span>
                                                </button>
                                            @endif
                                        @endfor
                                    @endif
                                @endforeach

                                @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                    <div class="pointer-events-none absolute inset-x-0 border-t border-line" style="top: {{ $y($m) }}px"></div>
                                @endfor

                                @foreach ($column['blocked'] as $block)
                                    <div class="absolute inset-x-0 z-[1] flex items-start justify-center border-y border-rose-500/40 bg-rose-500/10 pt-1 text-[10px] font-semibold tracking-wide text-rose-500 uppercase"
                                         style="top: {{ $y(max($block['from'], $grid['start'])) }}px; height: {{ (min($block['to'], $grid['end']) - max($block['from'], $grid['start'])) * $ppm }}px">
                                        {{ $block['reason'] ?: __('Not available') }}
                                    </div>
                                @endforeach

                                @foreach ($column['events'] as $event)
                                    @php
                                        $a = $event['appointment'];
                                        $h = max(18, ($event['to'] - $event['from']) * $ppm - 1);
                                        $color = $a->visitType?->color ?? $column['color'];
                                    @endphp
                                    <button type="button" wire:key="appt-{{ $a->id }}"
                                            x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })"
                                            class="absolute z-[2] overflow-hidden rounded-[2px] border-s-2 px-1.5 shadow-[inset_0_-1px_0_var(--color-canvas)] py-0.5 text-start transition hover:z-[3] hover:brightness-125 {{ $statusStyles[$a->status->value] }}"
                                            style="top: {{ $y($event['from']) + 0.5 }}px; height: {{ $h }}px; inset-inline-start: calc({{ $event['lane'] }} / {{ $event['lanes'] }} * 100% + 1px); width: calc(100% / {{ $event['lanes'] }} - 2px); border-inline-start-color: {{ $color }}; background: color-mix(in oklab, {{ $color }} 18%, var(--color-surface))">
                                        <span class="flex items-center gap-1 text-[11px] leading-4 font-semibold text-ink">
                                            <span class="size-1.5 shrink-0 rounded-full {{ $statusDot[$a->status->value] }}" title="{{ $a->status->label() }}"></span>
                                            @if ($a->queue_number)<span class="font-mono text-[10px] text-muted">#{{ $a->queue_number }}</span>@endif
                                            <span class="truncate">{{ $a->patient->name }}</span>
                                        </span>
                                        @if ($h > 30)
                                            <span class="block truncate text-[10px] text-muted"><span class="font-mono" dir="ltr">{{ $a->starts_at->format('H:i') }}</span> · {{ $a->visitType?->name() }}</span>
                                        @endif
                                    </button>
                                @endforeach

                                @if ($column['is_today'] && $nowMinute >= $grid['start'] && $nowMinute <= $grid['end'])
                                    <div class="pointer-events-none absolute inset-x-0 z-[4] border-t border-primary-400" style="top: {{ $y($nowMinute) }}px">
                                        <span class="absolute -start-px -top-[3px] size-1.5 bg-primary-400"></span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Status legend --}}
        <div class="mt-2 hidden flex-wrap gap-3 text-[11px] text-muted md:flex">
            @foreach (AppointmentStatus::cases() as $status)
                <span class="flex items-center gap-1.5"><span class="size-1.5 rounded-full {{ $statusDot[$status->value] }}"></span>{{ $status->label() }}</span>
            @endforeach
        </div>

        {{-- Agenda (phones) --}}
        <div class="space-y-3 md:hidden">
            @foreach ($grid['columns'] as $column)
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line bg-subtle px-3 py-2">
                        <p class="flex items-center gap-2 text-xs font-semibold text-ink"><span class="size-2 rounded-[1px]" style="background: {{ $column['color'] }}"></span>{{ $column['label'] }}</p>
                        <span class="text-[11px] text-muted">{{ $column['sublabel'] }}</span>
                    </div>
                    @forelse ($column['events'] as $event)
                        @php $a = $event['appointment']; @endphp
                        <button type="button" x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })" class="flex w-full items-center gap-3 border-b border-line px-3 py-2 text-start last:border-0">
                            <span class="w-12 shrink-0 font-mono text-xs text-ink" dir="ltr">{{ $a->starts_at->format('H:i') }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] text-ink">{{ $a->patient->name }}</span>
                                <span class="block truncate text-[11px] text-muted">{{ $a->visitType?->name() }}</span>
                            </span>
                            <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                        </button>
                    @empty
                        <p class="px-3 py-5 text-center text-xs text-muted">{{ __('No appointments.') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif

    <livewire:appointments.booking-form />
    <livewire:appointments.appointment-details />
    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
