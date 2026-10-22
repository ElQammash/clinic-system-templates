@php
    use App\Enums\AppointmentStatus;
    use Carbon\CarbonImmutable;

    /*
     * Warm & Friendly calendar: an agenda board instead of a time grid.
     * Each column (a doctor on the day view, a day on the week view) is a rounded card that lists,
     * in time order: appointment "stickers", blocked periods, and runs of free slots as tappable chips.
     */
    $hhmm = fn (int $m) => sprintf('%02d:%02d', intdiv($m, 60) % 24, $m % 60);
    $statusEmoji = [
        'booked' => '📌', 'confirmed' => '👍', 'arrived' => '🪑', 'in_progress' => '🩺',
        'completed' => '✅', 'cancelled' => '❌', 'no_show' => '👻',
    ];

    $agenda = function (array $column) use ($canBook) {
        $rows = [];
        $busy = [];
        foreach ($column['events'] as $event) {
            $rows[] = ['type' => 'event', 'at' => $event['from'], 'event' => $event];
            if ($event['appointment']->isActive()) {
                $busy[] = [$event['from'], $event['to']];
            }
        }
        foreach ($column['blocked'] as $block) {
            $rows[] = ['type' => 'blocked', 'at' => $block['from'], 'block' => $block];
            $busy[] = [$block['from'], $block['to']];
        }
        if ($canBook) {
            $day = CarbonImmutable::parse($column['date']);
            foreach ($column['windows'] as $window) {
                $run = [];
                for ($m = $window['from']; $m + $column['slot'] <= $window['to']; $m += $column['slot']) {
                    $free = $day->addMinutes($m)->isFuture()
                        && collect($busy)->every(fn ($b) => $m + $column['slot'] <= $b[0] || $m >= $b[1]);
                    if ($free) {
                        $run[] = $m;
                    } elseif ($run) {
                        $rows[] = ['type' => 'free', 'at' => $run[0], 'slots' => $run];
                        $run = [];
                    }
                }
                if ($run) {
                    $rows[] = ['type' => 'free', 'at' => $run[0], 'slots' => $run];
                }
            }
        }

        return collect($rows)->sort(fn ($x, $y) => ($x['at'] <=> $y['at']) ?: (($x['type'] === 'free') <=> ($y['type'] === 'free')))->values();
    };
@endphp
<div>
    <x-page-header :title="__('Appointments')" :subtitle="$rangeLabel" emoji="calendar">
        <x-slot:actions>
            @if ($canBook)
                <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $doctorId ?: 'null' }} })" class="btn btn-primary">
                    <x-icon name="plus" class="size-5" /> {{ __('Book appointment') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Toolbar --}}
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex items-center gap-1 rounded-full bg-white p-1.5 shadow-(--shadow-soft)">
            <button type="button" wire:click="previous" class="grid size-10 place-items-center rounded-full hover:bg-cream" aria-label="{{ __('Previous') }}"><x-icon name="chevron-right" class="size-5 ltr:rotate-180" /></button>
            <button type="button" wire:click="today" @class(['rounded-full px-4 py-2 text-[15px] font-bold', 'bg-primary-500 text-white' => $isToday, 'hover:bg-cream' => ! $isToday])>{{ __('Today') }}</button>
            <button type="button" wire:click="next" class="grid size-10 place-items-center rounded-full hover:bg-cream" aria-label="{{ __('Next') }}"><x-icon name="chevron-left" class="size-5 ltr:rotate-180" /></button>
            <input type="date" wire:model.live="date" class="ms-1 w-40 rounded-full border-0 bg-cream/60 px-4 py-2 text-[15px] font-semibold focus:ring-2 focus:ring-primary-200">
        </div>

        <div class="flex rounded-full bg-white p-1.5 shadow-(--shadow-soft)">
            @foreach (['day' => [__('Day'), '☀️'], 'week' => [__('Week'), '🗓️']] as $key => [$label, $emoji])
                <button type="button" wire:click="setView('{{ $key }}')" @class(['flex items-center gap-1.5 rounded-full px-4 py-2 text-[15px] font-bold transition', 'bg-ink text-white' => $view === $key, 'text-stone-600 hover:bg-cream' => $view !== $key])><span class="emoji">{{ $emoji }}</span> {{ $label }}</button>
            @endforeach
        </div>

        @if ($this->doctors->count() > 1)
            <div class="flex flex-wrap items-center gap-2 lg:ms-auto">
                @if ($view === 'day')
                    <button type="button" wire:click="selectDoctor(0)" @class(['rounded-full px-4 py-2 text-sm font-bold transition', 'bg-primary-500 text-white' => ! $doctorId, 'bg-white text-stone-600 shadow-(--shadow-soft) hover:text-ink' => $doctorId])>{{ __('All doctors') }}</button>
                @endif
                @foreach ($this->doctors as $doc)
                    <button type="button" wire:click="selectDoctor({{ $doc->id }})" @class([
                        'flex items-center gap-2 rounded-full py-1.5 ps-1.5 pe-4 text-sm font-bold transition',
                        'bg-primary-500 text-white' => $doctorId === $doc->id,
                        'bg-white text-stone-600 shadow-(--shadow-soft) hover:text-ink' => $doctorId !== $doc->id,
                    ])>
                        <span class="grid size-7 place-items-center rounded-full text-xs text-white ring-2 ring-white" style="background: {{ $doc->doctorProfile?->color }}">{{ mb_substr(preg_replace('/^(د\.|Dr\.?)\s*/u', '', $doc->displayName()), 0, 1) }}</span>
                        {{ $doc->displayName() }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (empty($grid['columns']))
        <div class="card px-5 py-16 text-center"><span class="emoji text-5xl">🧑‍⚕️</span><p class="mt-3 font-bold text-muted">{{ __('No doctors yet. Add a doctor from Users.') }}</p></div>
    @else
        <div @class([
            'grid gap-4',
            'md:grid-cols-[repeat(auto-fit,minmax(20rem,1fr))]' => $view === 'day',
            'sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7' => $view === 'week',
        ]) wire:loading.class="opacity-60">
            @foreach ($grid['columns'] as $column)
                @php
                    $rows = $agenda($column);
                    $count = collect($column['events'])->filter(fn ($e) => $e['appointment']->isActive() || $e['appointment']->status === AppointmentStatus::Completed)->count();
                @endphp
                <section wire:key="col-{{ $column['key'] }}" @class(['flex flex-col overflow-hidden rounded-[1.75rem] border-2 bg-white shadow-(--shadow-soft)', 'border-primary-300' => $column['is_today'] && $view === 'week', 'border-line' => ! ($column['is_today'] && $view === 'week')])>
                    <header class="flex items-center gap-3 px-4 pt-4 pb-3" style="background: linear-gradient(to bottom, color-mix(in oklab, {{ $column['color'] }} 14%, white), white)">
                        <span class="h-10 w-2 rounded-full" style="background: {{ $column['color'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-lg leading-tight font-extrabold">{{ $column['label'] }}</p>
                            <p class="truncate text-sm text-muted">{{ $column['sublabel'] }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-sm font-extrabold shadow-(--shadow-soft)">{{ $count }}</span>
                    </header>

                    <div class="flex-1 space-y-2 px-3 pb-4">
                        @forelse ($rows as $row)
                            @if ($row['type'] === 'event')
                                @php
                                    $a = $row['event']['appointment'];
                                    $color = $a->visitType?->color ?? $column['color'];
                                    $faded = in_array($a->status, [AppointmentStatus::Cancelled, AppointmentStatus::NoShow, AppointmentStatus::Completed], true);
                                @endphp
                                <button type="button" wire:key="appt-{{ $a->id }}" x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })"
                                        @class(['flex w-full items-center gap-3 rounded-2xl p-2.5 text-start transition hover:-translate-y-0.5 hover:shadow-(--shadow-soft)', 'opacity-55' => $faded])
                                        style="background: color-mix(in oklab, {{ $color }} 12%, white)">
                                    <span class="w-12 shrink-0 rounded-xl bg-white py-1 text-center text-sm font-extrabold ltr-nums">{{ $a->starts_at->format('H:i') }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span @class(['block truncate font-bold', 'line-through' => $a->status === AppointmentStatus::Cancelled])>{{ $a->patient->name }}</span>
                                        <span class="block truncate text-xs font-semibold text-muted">{{ $a->visitType?->name() }}@if ($a->queue_number) · #{{ $a->queue_number }}@endif</span>
                                    </span>
                                    <span class="emoji text-lg" title="{{ $a->status->label() }}">{{ $statusEmoji[$a->status->value] }}</span>
                                </button>
                            @elseif ($row['type'] === 'blocked')
                                <div class="flex items-center gap-2 rounded-2xl border-2 border-dashed border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700">
                                    <span class="emoji">🚫</span>
                                    <span class="ltr-nums">{{ $hhmm(max(0, $row['block']['from'])) }}–{{ $hhmm(min(1440, $row['block']['to'])) }}</span>
                                    <span class="truncate">{{ $row['block']['reason'] ?: __('Not available') }}</span>
                                </div>
                            @else
                                <div x-data="{ all: false }" class="rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/50 p-2">
                                    <p class="mb-1.5 flex items-center gap-1.5 px-1 text-xs font-bold text-emerald-800"><span class="emoji">🌱</span> {{ __('Free times') }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($row['slots'] as $i => $m)
                                            <button type="button" @if ($i >= 6) x-show="all" x-cloak @endif
                                                    x-on:click="$dispatch('book-appointment', { doctorId: {{ $column['doctor_id'] }}, start: '{{ CarbonImmutable::parse($column['date'])->addMinutes($m)->format('Y-m-d H:i') }}' })"
                                                    class="rounded-full bg-white px-2.5 py-1 text-xs font-extrabold text-emerald-800 shadow-[0_2px_0_rgb(16_185_129/0.25)] transition hover:bg-emerald-500 hover:text-white ltr-nums">
                                                {{ $hhmm($m) }}
                                            </button>
                                        @endforeach
                                        @if (count($row['slots']) > 6)
                                            <button type="button" x-show="!all" x-on:click="all = true" class="rounded-full px-2.5 py-1 text-xs font-bold text-emerald-700 hover:underline">+{{ count($row['slots']) - 6 }}</button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="py-10 text-center"><span class="emoji text-4xl">😴</span><p class="mt-2 text-sm font-bold text-muted">{{ __('No appointments.') }}</p></div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap gap-2 text-sm font-semibold text-muted">
            @foreach (AppointmentStatus::cases() as $status)
                <span class="rounded-full bg-white px-3 py-1 shadow-(--shadow-soft)"><span class="emoji">{{ $statusEmoji[$status->value] }}</span> {{ $status->label() }}</span>
            @endforeach
        </div>
    @endif

    <livewire:appointments.booking-form />
    <livewire:appointments.appointment-details />
    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
