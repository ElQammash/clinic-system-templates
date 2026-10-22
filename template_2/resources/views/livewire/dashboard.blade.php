@php
    use App\Support\Format;

    // Inline SVG sparkline path (0..100 x 0..28) from a series of numbers.
    $spark = function ($values) {
        $values = collect($values)->values();
        if ($values->count() < 2) return '';
        $max = max(1, $values->max());
        $step = 100 / ($values->count() - 1);
        return $values->map(fn ($v, $i) => round($i * $step, 2).','.round(28 - ($v / $max) * 26, 2))->implode(' ');
    };
    $series = $revenueChart->values();
    $yesterday = $series->count() > 1 ? $series[$series->count() - 2] : 0;

    $chart = [
        'type' => 'bar',
        'data' => [
            'labels' => $revenueChart->keys()->map(fn ($d) => Format::date($d, 'j/n'))->values(),
            'datasets' => [['label' => __('Revenue'), 'data' => $series, 'backgroundColor' => 'primary', 'borderRadius' => 2, 'maxBarThickness' => 14]],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10]], 'y' => ['beginAtZero' => true, 'ticks' => ['maxTicksLimit' => 4]]],
        ],
    ];

    $tiles = array_values(array_filter([
        $seesAppointments ? ['label' => __('Appointments today'), 'value' => Format::number($today['appointments']), 'meta' => __(':done done · :waiting waiting', ['done' => Format::number($counts['done']), 'waiting' => Format::number($counts['waiting'])]), 'tone' => 'text-primary-400'] : null,
        $seesMoney ? ['label' => __('Revenue today'), 'value' => money($today['revenue']), 'meta' => __('Yesterday: :amount', ['amount' => money($yesterday)]), 'tone' => 'text-primary-400', 'spark' => $spark($series->slice(-14))] : null,
        $seesMoney ? ['label' => __('Revenue this month'), 'value' => money($month['revenue']), 'meta' => $isFinancial && $month['net'] !== null ? __('Net after expenses: :amount', ['amount' => money($month['net'])]) : __('Collected payments'), 'tone' => 'text-secondary-500', 'spark' => $spark($series)] : null,
        ! $seesMoney ? ['label' => __('Visits this month'), 'value' => Format::number($month['visits']), 'meta' => __('Completed visits'), 'tone' => 'text-primary-400'] : null,
        ['label' => __('New patients this month'), 'value' => Format::number($month['new_patients']), 'meta' => __('No-show rate: :rate%', ['rate' => Format::number($month['no_show_rate'], 1)]), 'tone' => 'text-violet-500'],
        $seesMoney ? ['label' => __('Outstanding (all patients)'), 'value' => money($outstanding), 'meta' => __('Unpaid balances'), 'tone' => 'text-rose-500'] : null,
        $seesAppointments ? ['label' => __('Waiting now'), 'value' => Format::number($counts['waiting']), 'meta' => __(':count with doctor', ['count' => Format::number($counts['with_doctor'])]), 'tone' => 'text-amber-500'] : null,
    ]));
@endphp
<div>
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3 border-b border-line pb-4">
        <div>
            <p class="font-mono text-[11px] tracking-widest text-muted uppercase">{{ Format::longDate(now()) }}</p>
            <h1 class="mt-1 text-lg font-semibold text-ink">{{ __(':greeting, :name', ['greeting' => now()->hour < 12 ? __('Good morning') : (now()->hour < 18 ? __('Good afternoon') : __('Good evening')), 'name' => $user->displayName()]) }}</h1>
        </div>
        <div class="flex items-center gap-2 text-xs text-muted">
            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-primary-400 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-primary-500"></span></span>
            {{ __('Live') }} · <span class="font-mono" x-data="{ t: '' }" x-init="const f = () => t = new Date().toLocaleTimeString('{{ app()->getLocale() }}-EG', { hour: '2-digit', minute: '2-digit' }); f(); setInterval(f, 15000)" x-text="t"></span>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-(--radius-card) border border-line bg-line md:grid-cols-3 xl:grid-cols-6">
        @foreach ($tiles as $tile)
            <div @class(["relative bg-surface p-4", "pb-10" => ! empty($tile["spark"])])>
                <p class="text-[10.5px] font-semibold tracking-wider text-muted uppercase">{{ $tile['label'] }}</p>
                <p class="mt-2 font-mono text-xl font-semibold text-ink">{{ $tile['value'] }}</p>
                <p class="mt-1 truncate text-[11px] {{ $tile['tone'] }}">{{ $tile['meta'] }}</p>
                @if (! empty($tile['spark']))
                    <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="absolute inset-x-4 bottom-2 h-6 w-[calc(100%-2rem)] opacity-50 ltr:scale-x-100 rtl:-scale-x-100" aria-hidden="true">
                        <polyline points="{{ $tile['spark'] }}" fill="none" stroke="currentColor" stroke-width="1.5" class="{{ $tile['tone'] }}" vector-effect="non-scaling-stroke" />
                    </svg>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[1fr_26rem]">
        <div class="min-w-0 space-y-4">
            @if ($seesMoney)
                <section class="card">
                    <div class="flex items-center justify-between border-b border-line px-4 py-2.5">
                        <h2 class="text-[13px] font-semibold text-ink">{{ __('Revenue — last 30 days') }}</h2>
                        @if (Route::has('reports.index') && App\Livewire\Reports\Index::canView($user))
                            <a href="{{ route('reports.index') }}" wire:navigate class="text-xs text-primary-400 hover:underline">{{ __('Reports') }} →</a>
                        @endif
                    </div>
                    <div class="h-56 p-3" wire:ignore><canvas x-data="chart(@js($chart))"></canvas></div>
                </section>
            @endif

            @if ($byDoctor->isNotEmpty())
                <section class="card overflow-hidden">
                    <h2 class="border-b border-line px-4 py-2.5 text-[13px] font-semibold text-ink">{{ __('Doctors this month') }}</h2>
                    <table class="table-clean">
                        <thead><tr><th>{{ __('Doctor') }}</th><th class="text-end">{{ __('Visits') }}</th><th class="text-end">{{ __('Revenue') }}</th><th class="w-1/3"></th></tr></thead>
                        <tbody>
                            @php $max = max(1, $byDoctor->max('revenue')); @endphp
                            @foreach ($byDoctor as $row)
                                <tr>
                                    <td class="font-medium text-ink"><span class="me-2 inline-block size-2 rounded-[1px]" style="background: {{ $row['color'] }}"></span>{{ $row['doctor'] }}</td>
                                    <td class="num text-end">{{ Format::number($row['visits']) }}</td>
                                    <td class="num text-end font-semibold text-ink">{{ money($row['revenue']) }}</td>
                                    <td><div class="h-1 bg-subtle"><div class="h-1" style="width: {{ $row['revenue'] / $max * 100 }}%; background: {{ $row['color'] }}"></div></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif

            @if ($checklist)
                <section class="card">
                    <div class="flex items-center justify-between border-b border-line px-4 py-2.5">
                        <h2 class="text-[13px] font-semibold text-ink">{{ __('Getting started') }}</h2>
                        <span class="font-mono text-xs text-primary-400">{{ collect($checklist)->where('done', true)->count() }}/{{ count($checklist) }}</span>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($checklist as $step)
                            <li class="flex items-center gap-3 px-4 py-2 text-[13px]">
                                <span @class(['grid size-4 place-items-center rounded-[2px] border', 'border-primary-500 bg-primary-500 text-slate-950' => $step['done'], 'border-line-strong' => ! $step['done']])>@if ($step['done'])<x-icon name="check" class="size-3" />@endif</span>
                                <span @class(['flex-1', 'text-muted line-through' => $step['done'], 'text-ink' => ! $step['done']])>{{ $step['label'] }}</span>
                                @if (! $step['done'] && $step['url'])<a href="{{ $step['url'] }}" wire:navigate class="text-xs text-primary-400 hover:underline">{{ __('Set up') }} →</a>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="space-y-4">
            @if ($seesAppointments)
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-4 py-2.5">
                        <h2 class="text-[13px] font-semibold text-ink">{{ __('Today\'s schedule') }}</h2>
                        <a href="{{ route('queue') }}" wire:navigate class="text-xs text-primary-400 hover:underline">{{ __('Waiting room') }} →</a>
                    </div>
                    <div class="grid grid-cols-4 gap-px border-b border-line bg-line text-center">
                        @foreach (['remaining' => [__('Expected'), 'text-sky-500'], 'waiting' => [__('Waiting'), 'text-amber-500'], 'with_doctor' => [__('With doctor'), 'text-violet-500'], 'done' => [__('Done'), 'text-primary-400']] as $key => [$label, $tone])
                            <div class="bg-surface py-2"><p class="font-mono text-base font-semibold {{ $tone }}">{{ $counts[$key] }}</p><p class="text-[10px] tracking-wide text-muted uppercase">{{ $label }}</p></div>
                        @endforeach
                    </div>
                    <table class="table-clean">
                        <tbody>
                            @forelse ($todayAppointments->filter(fn ($a) => $a->isActive())->take(14) as $a)
                                <tr>
                                    <td class="w-16 font-mono text-xs text-muted" dir="ltr">{{ $a->starts_at->format('H:i') }}</td>
                                    <td class="w-full max-w-0 truncate text-ink">{{ $a->patient->name }}</td>
                                    <td class="text-end"><x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-center text-xs text-muted">{{ __('No more appointments today.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            @if ($lowStock->isNotEmpty())
                <section class="card border-amber-500/40">
                    <h2 class="flex items-center gap-2 border-b border-line px-4 py-2.5 text-[13px] font-semibold text-amber-500"><x-icon name="warning" class="size-4" /> {{ __('Low stock') }}</h2>
                    <ul class="divide-y divide-line text-[13px]">
                        @foreach ($lowStock as $item)
                            <li class="flex justify-between px-4 py-2"><span class="text-ink">{{ $item->name }}</span><span class="font-mono text-rose-500">{{ $item->quantity }} {{ $item->unit }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</div>
