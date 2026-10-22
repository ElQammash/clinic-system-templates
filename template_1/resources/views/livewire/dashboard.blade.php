@php
    use App\Support\Format;

    $hour = now()->hour;
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));

    $chart = [
        'type' => 'line',
        'data' => [
            'labels' => $revenueChart->keys()->map(fn ($d) => Format::date($d, 'j M'))->values(),
            'datasets' => [[
                'label' => __('Revenue'),
                'data' => $revenueChart->values(),
                'borderColor' => 'primary',
                'backgroundColor' => 'primary-soft',
                'fill' => true,
                'tension' => 0.35,
                'cubicInterpolationMode' => 'monotone', // no overshoot below zero between days
                'pointRadius' => 0,
                'borderWidth' => 2,
            ]],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8]], 'y' => ['beginAtZero' => true, 'ticks' => ['maxTicksLimit' => 5]]],
            'interaction' => ['intersect' => false, 'mode' => 'index'],
        ],
    ];
@endphp
<div>
    <div class="mb-8 flex flex-col gap-1">
        <p class="text-sm font-medium text-primary-700">{{ Format::longDate(now()) }}</p>
        <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ __(':greeting, :name', ['greeting' => $greeting, 'name' => $user->displayName()]) }}</h1>
        <p class="text-sm text-muted">{{ __('Here is an overview of your clinic today.') }}</p>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @if ($seesAppointments)
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted">{{ __('Appointments today') }}</p>
                    <span class="grid size-9 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="calendar" class="size-5" /></span>
                </div>
                <p class="mt-3 text-3xl font-bold text-ink">{{ Format::number($today['appointments']) }}</p>
                <p class="mt-1 text-xs text-muted">{{ __(':done done · :waiting waiting', ['done' => Format::number($counts['done']), 'waiting' => Format::number($counts['waiting'])]) }}</p>
            </div>
        @endif

        @if ($seesMoney)
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted">{{ __('Revenue today') }}</p>
                    <span class="grid size-9 place-items-center rounded-lg bg-emerald-50 text-emerald-600"><x-icon name="cash" class="size-5" /></span>
                </div>
                <p class="mt-3 text-3xl font-bold text-ink">{{ money($today['revenue']) }}</p>
                <p class="mt-1 text-xs text-muted">{{ __('Collected payments') }}</p>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted">{{ __('Revenue this month') }}</p>
                    <span class="grid size-9 place-items-center rounded-lg bg-secondary-50 text-secondary-600"><x-icon name="trend" class="size-5" /></span>
                </div>
                <p class="mt-3 text-3xl font-bold text-ink">{{ money($month['revenue']) }}</p>
                @if ($isFinancial && $month['net'] !== null)
                    <p class="mt-1 text-xs {{ $month['net'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ __('Net after expenses: :amount', ['amount' => money($month['net'])]) }}</p>
                @endif
            </div>
        @else
            <div class="card p-5">
                <p class="text-sm font-medium text-muted">{{ __('Visits this month') }}</p>
                <p class="mt-3 text-3xl font-bold text-ink">{{ Format::number($month['visits']) }}</p>
            </div>
        @endif

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-muted">{{ __('New patients this month') }}</p>
                <span class="grid size-9 place-items-center rounded-lg bg-violet-50 text-violet-600"><x-icon name="user-plus" class="size-5" /></span>
            </div>
            <p class="mt-3 text-3xl font-bold text-ink">{{ Format::number($month['new_patients']) }}</p>
            <p class="mt-1 text-xs text-muted">{{ __('No-show rate: :rate%', ['rate' => Format::number($month['no_show_rate'], 1)]) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Revenue chart --}}
        @if ($seesMoney)
            <section class="card p-5 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-ink">{{ __('Revenue — last 30 days') }}</h2>
                    @if (Route::has('reports.index') && App\Livewire\Reports\Index::canView($user))
                        <a href="{{ route('reports.index') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:underline">{{ __('Reports') }}</a>
                    @endif
                </div>
                <div class="h-64" wire:ignore>
                    <canvas x-data="chart(@js($chart))"></canvas>
                </div>
            </section>
        @endif

        {{-- Today's agenda --}}
        @if ($seesAppointments)
            <section @class(['card overflow-hidden', 'lg:col-span-3' => ! $seesMoney])>
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-semibold text-ink">{{ __('Today\'s schedule') }}</h2>
                    <a href="{{ route('queue') }}" wire:navigate class="text-sm font-medium text-primary-700 hover:underline">{{ __('Waiting room') }}</a>
                </div>
                <div class="grid grid-cols-4 divide-x divide-line border-b border-line text-center rtl:divide-x-reverse">
                    @foreach (['remaining' => __('Expected'), 'waiting' => __('Waiting'), 'with_doctor' => __('With doctor'), 'done' => __('Done')] as $key => $label)
                        <div class="px-2 py-3"><p class="text-lg font-bold text-ink">{{ Format::number($counts[$key]) }}</p><p class="text-[11px] text-muted">{{ $label }}</p></div>
                    @endforeach
                </div>
                <ul class="max-h-72 divide-y divide-line overflow-y-auto">
                    @forelse ($todayAppointments->filter(fn ($a) => $a->isActive())->take(12) as $a)
                        <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                            <span class="w-16 shrink-0 font-semibold ltr-nums">{{ Format::time($a->starts_at) }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ $a->patient->name }}</span>
                            <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-muted">{{ __('No more appointments today.') }}</li>
                    @endforelse
                </ul>
            </section>
        @endif
    </div>

    @if ($byDoctor->isNotEmpty() || $outstanding > 0 || $lowStock->isNotEmpty())
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            @if ($byDoctor->isNotEmpty())
                <section class="card overflow-hidden lg:col-span-2">
                    <h2 class="border-b border-line px-5 py-4 font-semibold text-ink">{{ __('Doctors this month') }}</h2>
                    <ul class="divide-y divide-line">
                        @php $max = max(1, $byDoctor->max('revenue')); @endphp
                        @foreach ($byDoctor as $row)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 font-medium"><span class="size-2.5 rounded-full" style="background: {{ $row['color'] }}"></span>{{ $row['doctor'] }}</span>
                                    <span><span class="font-semibold">{{ money($row['revenue']) }}</span> <span class="text-xs text-muted">· {{ trans_choice(':count visit|:count visits', $row['visits'], ['count' => Format::number($row['visits'])]) }}</span></span>
                                </div>
                                <div class="mt-2 h-1.5 rounded-full bg-slate-100"><div class="h-1.5 rounded-full" style="width: {{ $row['revenue'] / $max * 100 }}%; background: {{ $row['color'] }}"></div></div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            <div class="space-y-6">
                @if ($outstanding > 0)
                    <a href="{{ route('invoices.index', ['status' => 'partially_paid']) }}" wire:navigate class="card block border-rose-100 bg-rose-50/40 p-5 transition hover:shadow-(--shadow-lift)">
                        <p class="text-sm text-rose-700">{{ __('Outstanding (all patients)') }}</p>
                        <p class="mt-2 text-2xl font-bold text-rose-700">{{ money($outstanding) }}</p>
                    </a>
                @endif
                @if ($lowStock->isNotEmpty())
                    <section class="card p-5">
                        <h2 class="mb-3 flex items-center gap-2 font-semibold text-ink"><x-icon name="warning" class="size-5 text-amber-500" /> {{ __('Low stock') }}</h2>
                        <ul class="space-y-1.5 text-sm">
                            @foreach ($lowStock as $item)
                                <li class="flex justify-between"><span>{{ $item->name }}</span><span class="font-semibold text-rose-600">{{ Format::number($item->quantity) }} {{ $item->unit }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    @endif

    @if ($checklist)
        <div class="card mt-8 overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-line px-6 py-4">
                <div>
                    <h2 class="font-semibold text-ink">{{ __('Getting started') }}</h2>
                    <p class="text-sm text-muted">{{ __('Complete these steps to get your clinic ready.') }}</p>
                </div>
                <span class="text-sm font-semibold text-primary-700">{{ Format::number(collect($checklist)->where('done', true)->count()) }}/{{ Format::number(count($checklist)) }}</span>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($checklist as $step)
                    <li class="flex items-center gap-4 px-6 py-3">
                        <span @class(['grid size-7 place-items-center rounded-full', 'bg-primary-600 text-white' => $step['done'], 'border-2 border-dashed border-slate-300 text-slate-300' => ! $step['done']])><x-icon name="check" class="size-4" /></span>
                        <span @class(['flex-1 text-sm', 'text-muted line-through' => $step['done'], 'font-medium text-ink' => ! $step['done']])>{{ $step['label'] }}</span>
                        @if (! $step['done'] && $step['url'])
                            <a href="{{ $step['url'] }}" wire:navigate class="btn btn-secondary btn-sm">{{ __('Set up') }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
