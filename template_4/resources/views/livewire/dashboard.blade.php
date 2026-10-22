@php
    use App\Support\Format;

    $greeting = now()->hour < 12 ? __('Good morning') : (now()->hour < 18 ? __('Good afternoon') : __('Good evening'));

    // A single hairline gold line, no fill, no grid — quiet and editorial.
    $chart = [
        'type' => 'line',
        'data' => [
            'labels' => $revenueChart->keys()->map(fn ($d) => Format::date($d, 'j/n'))->values(),
            'datasets' => [['label' => __('Revenue'), 'data' => $revenueChart->values(), 'borderColor' => 'primary', 'borderWidth' => 1.25, 'pointRadius' => 0, 'pointHoverRadius' => 4, 'tension' => 0.3, 'cubicInterpolationMode' => 'monotone', 'fill' => false]],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'border' => ['display' => false], 'ticks' => ['maxTicksLimit' => 6]],
                'y' => ['beginAtZero' => true, 'border' => ['display' => false], 'grid' => ['drawTicks' => false], 'ticks' => ['maxTicksLimit' => 3, 'padding' => 10]],
            ],
        ],
    ];

    $figures = array_values(array_filter([
        $seesAppointments ? [__('Appointments today'), Format::number($today['appointments']), __(':done done · :waiting waiting', ['done' => Format::number($counts['done']), 'waiting' => Format::number($counts['waiting'])])] : null,
        $seesMoney ? [__('Revenue today'), money($today['revenue']), __('Collected payments')] : null,
        $seesMoney ? [__('Revenue this month'), money($month['revenue']), $isFinancial && $month['net'] !== null ? __('Net after expenses: :amount', ['amount' => money($month['net'])]) : __('Collected payments')] : null,
        ! $seesMoney ? [__('Visits this month'), Format::number($month['visits']), __('Completed visits')] : null,
        [__('New patients this month'), Format::number($month['new_patients']), __('No-show rate: :rate%', ['rate' => Format::number($month['no_show_rate'], 1)])],
    ]));
@endphp
<div>
    <header class="mb-14">
        <p class="eyebrow">{{ Format::longDate(now()) }}</p>
        <h1 class="mt-3 font-serif text-5xl leading-tight text-ink sm:text-6xl">{{ $greeting }}<span class="text-primary-500">{{ is_rtl() ? '،' : ',' }}</span><br class="sm:hidden"> {{ $user->displayName() }}</h1>
        <div class="gold-rule mt-6"></div>
    </header>

    {{-- Figures, separated by hairlines --}}
    <div class="grid border-y border-line sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($figures as [$label, $value, $hint])
            <div @class(['px-0 py-8 sm:px-8', 'sm:border-s sm:border-line' => ! $loop->first && $loop->index % 2 === 1, 'lg:border-s lg:border-line' => ! $loop->first, 'max-sm:border-t max-sm:border-line' => ! $loop->first])>
                <p class="eyebrow text-neutral-400">{{ $label }}</p>
                <p class="mt-4 font-serif text-4xl text-ink ltr-nums">{{ $value }}</p>
                <p class="mt-2 text-xs text-muted">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-16 grid gap-16 lg:grid-cols-[1fr_22rem]">
        <div class="min-w-0 space-y-16">
            @if ($seesMoney)
                <section>
                    <div class="mb-6 flex items-end justify-between">
                        <h2 class="font-serif text-3xl">{{ __('Revenue — last 30 days') }}</h2>
                        @if (Route::has('reports.index') && App\Livewire\Reports\Index::canView($user))
                            <a href="{{ route('reports.index') }}" wire:navigate class="eyebrow text-neutral-500 hover:text-primary-700">{{ __('Reports') }}</a>
                        @endif
                    </div>
                    <div class="h-64" wire:ignore><canvas x-data="chart(@js($chart))"></canvas></div>
                    @if ($outstanding > 0)
                        <p class="mt-6 border-s border-primary-500 ps-4 text-sm text-neutral-600">{{ __('Outstanding (all patients)') }} — <span class="font-serif text-lg text-ink ltr-nums">{{ money($outstanding) }}</span></p>
                    @endif
                </section>
            @endif

            @if ($byDoctor->isNotEmpty())
                <section>
                    <h2 class="mb-6 font-serif text-3xl">{{ __('Doctors this month') }}</h2>
                    @php $max = max(1, $byDoctor->max('revenue')); @endphp
                    <div class="divide-y divide-line border-y border-line">
                        @foreach ($byDoctor as $row)
                            <div class="grid grid-cols-[1fr_auto] items-center gap-x-6 gap-y-3 py-5">
                                <p class="font-serif text-xl">{{ $row['doctor'] }}</p>
                                <p class="text-end"><span class="font-serif text-xl ltr-nums">{{ money($row['revenue']) }}</span> <span class="ms-3 text-xs text-muted">{{ trans_choice(':count visit|:count visits', $row['visits'], ['count' => Format::number($row['visits'])]) }}</span></p>
                                <div class="col-span-2 h-px bg-line"><div class="h-px bg-primary-500" style="width: {{ $row['revenue'] / $max * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($checklist)
                <section>
                    <h2 class="mb-6 font-serif text-3xl">{{ __('Getting started') }} <span class="text-lg text-primary-600">{{ collect($checklist)->where('done', true)->count() }}/{{ count($checklist) }}</span></h2>
                    <ol class="divide-y divide-line border-y border-line">
                        @foreach ($checklist as $step)
                            <li class="flex items-center gap-6 py-4">
                                <span @class(['font-serif text-lg', 'text-primary-500' => $step['done'], 'text-neutral-300' => ! $step['done']])>{{ $step['done'] ? '✓' : str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span @class(['flex-1 text-[15px]', 'text-neutral-400 line-through decoration-primary-400' => $step['done']])>{{ $step['label'] }}</span>
                                @if (! $step['done'] && $step['url'])<a href="{{ $step['url'] }}" wire:navigate class="eyebrow hover:text-ink">{{ __('Set up') }}</a>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        <aside class="space-y-16">
            @if ($seesAppointments)
                <section>
                    <div class="mb-6 flex items-end justify-between">
                        <h2 class="font-serif text-3xl">{{ __('Today') }}</h2>
                        <a href="{{ route('queue') }}" wire:navigate class="eyebrow text-neutral-500 hover:text-primary-700">{{ __('Waiting room') }}</a>
                    </div>
                    <dl class="mb-6 grid grid-cols-4 border-y border-line text-center">
                        @foreach (['remaining' => __('Expected'), 'waiting' => __('Waiting'), 'with_doctor' => __('With doctor'), 'done' => __('Done')] as $key => $label)
                            <div @class(['py-4', 'border-s border-line' => ! $loop->first])><dt class="text-[10px] text-muted">{{ $label }}</dt><dd class="mt-1 font-serif text-2xl">{{ Format::number($counts[$key]) }}</dd></div>
                        @endforeach
                    </dl>
                    <ol class="space-y-5">
                        @forelse ($todayAppointments->filter(fn ($a) => $a->isActive())->take(9) as $a)
                            <li class="grid grid-cols-[3.5rem_1fr] gap-3">
                                <span class="font-serif text-lg text-primary-600 ltr-nums">{{ $a->starts_at->format('H:i') }}</span>
                                <div class="min-w-0 border-b border-line pb-4">
                                    <p class="truncate text-[15px] text-ink">{{ $a->patient->name }}</p>
                                    <div class="mt-1 flex items-center justify-between gap-2 text-xs text-muted">
                                        <span class="truncate">{{ $a->doctor->displayName() }}</span>
                                        <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="font-serif text-xl text-muted italic">{{ __('No more appointments today.') }}</li>
                        @endforelse
                    </ol>
                </section>
            @endif

            @if ($lowStock->isNotEmpty())
                <section>
                    <h2 class="mb-4 font-serif text-2xl">{{ __('Low stock') }}</h2>
                    <ul class="divide-y divide-line border-y border-line text-sm">
                        @foreach ($lowStock as $item)
                            <li class="flex justify-between py-3"><span>{{ $item->name }}</span><span class="text-rose-700 ltr-nums">{{ $item->quantity }} {{ $item->unit }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>
