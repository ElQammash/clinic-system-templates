@php
    use App\Support\Format;

    $kpis = array_filter([
        'revenue' => [__('Revenue collected'), money($summary['revenue']), 'emerald'],
        'invoiced' => [__('Invoiced'), money($summary['invoiced']), 'primary'],
        'expenses' => $financial && $summary['expenses'] !== null ? [__('Expenses'), money($summary['expenses']), 'rose'] : null,
        'net' => $financial && $summary['net'] !== null ? [__('Net profit'), money($summary['net']), $summary['net'] >= 0 ? 'emerald' : 'rose'] : null,
        'visits' => [__('Visits'), Format::number($summary['visits']), 'violet'],
        'new_patients' => [__('New patients'), Format::number($summary['new_patients']), 'sky'],
        'no_show_rate' => [__('No-show rate'), Format::number($summary['no_show_rate'], 1).'%', 'orange'],
        'outstanding' => [__('Outstanding'), money($summary['outstanding']), 'amber'],
    ]);

    $dailyChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $byDay->keys()->map(fn ($d) => Format::date($d, 'j M'))->values(),
            'datasets' => [['label' => __('Revenue'), 'data' => $byDay->values(), 'backgroundColor' => 'primary', 'borderRadius' => 4, 'maxBarThickness' => 28]],
        ],
        'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 12]], 'y' => ['beginAtZero' => true]]],
    ];

    $methodColors = ['#f25c49', '#fb923c', '#7c3aed', '#14b8a6', '#a8a29e'];
    $methodChart = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $byMethod->keys()->map(fn ($m) => $methods[$m] ?? $m)->values(),
            'datasets' => [['data' => $byMethod->values(), 'backgroundColor' => array_slice($methodColors, 0, max(1, $byMethod->count())), 'borderWidth' => 0]],
        ],
        'options' => ['maintainAspectRatio' => false, 'cutout' => '65%', 'plugins' => ['legend' => ['position' => 'bottom']]],
    ];

    $ieChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $incomeVsExpenses->keys()->map(fn ($m) => Format::date($m.'-01', 'F Y'))->values(),
            'datasets' => [
                ['label' => __('Income'), 'data' => $incomeVsExpenses->pluck('income')->values(), 'backgroundColor' => 'primary', 'borderRadius' => 4],
                ['label' => __('Expenses'), 'data' => $incomeVsExpenses->pluck('expenses')->values(), 'backgroundColor' => '#fb7185', 'borderRadius' => 4],
            ],
        ],
        'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['beginAtZero' => true]]],
    ];
    $chartKey = md5($from.$to.$doctorId);
@endphp
<div>
    <x-page-header emoji="chart" :title="__('Reports')" :subtitle="Format::date($from).' — '.Format::date($to)">
        <x-slot:actions>
            <div x-data="{ open: false }" class="relative">
                <button type="button" x-on:click="open = !open" class="btn btn-secondary"><x-icon name="export" class="size-4" /> {{ __('Export') }}</button>
                <div x-show="open" x-cloak x-on:click.outside="open = false" class="absolute end-0 z-20 mt-1 w-56 rounded-2xl border border-line bg-white py-1 shadow-(--shadow-lift)">
                    <a href="{{ route('reports.pdf', $exportQuery) }}" target="_blank" class="block px-4 py-2 text-sm hover:bg-primary-50">PDF — {{ __('Summary') }}</a>
                    @foreach (['summary' => __('Summary'), 'daily' => __('Revenue by day'), 'doctors' => __('Revenue by doctor'), 'services' => __('Revenue by service'), 'payments' => __('Payments list'), 'appointments' => __('Appointments list')] + ($financial ? ['expenses' => __('Expenses list')] : []) as $sheet => $label)
                        <a href="{{ route('reports.excel', ['sheet' => $sheet] + $exportQuery) }}" class="block px-4 py-2 text-sm hover:bg-primary-50">Excel — {{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="card mb-6 flex flex-col gap-3 p-4 lg:flex-row lg:items-center">
        <div class="flex flex-wrap gap-1">
            @foreach (['today' => __('Today'), 'yesterday' => __('Yesterday'), 'this_week' => __('This week'), 'this_month' => __('This month'), 'last_month' => __('Last month'), 'last_30' => __('Last 30 days'), 'this_year' => __('This year')] as $key => $label)
                <button type="button" wire:click="$set('period', '{{ $key }}')" @class(['btn btn-sm', 'btn-primary' => $period === $key, 'btn-ghost' => $period !== $key])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex items-center gap-2 lg:ms-auto">
            <input wire:model.live="from" type="date" class="form-control w-40 py-1.5">
            <span class="text-muted">—</span>
            <input wire:model.live="to" type="date" class="form-control w-40 py-1.5">
            @if ($canPickDoctor)
                <select wire:model.live="doctorId" class="form-control w-48 py-1.5">
                    <option value="0">{{ __('All doctors') }}</option>
                    @foreach ($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->displayName() }}</option>@endforeach
                </select>
            @endif
        </div>
    </div>

    <div wire:loading.class="opacity-60" class="transition">
        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($kpis as $key => [$label, $value, $color])
                <div class="card p-5">
                    <p class="text-sm text-muted">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold text-ink">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <section class="card p-5 lg:col-span-2">
                <h2 class="mb-4 font-semibold text-ink">{{ __('Revenue by day') }}</h2>
                <div class="h-72" wire:key="daily-{{ $chartKey }}"><canvas x-data="chart(@js($dailyChart))"></canvas></div>
            </section>
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-ink">{{ __('By payment method') }}</h2>
                @if ($byMethod->isEmpty())
                    <p class="py-16 text-center text-sm text-muted">{{ __('No payments in this period.') }}</p>
                @else
                    <div class="h-72" wire:key="method-{{ $chartKey }}"><canvas x-data="chart(@js($methodChart))"></canvas></div>
                @endif
            </section>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 font-semibold text-ink">{{ __('Revenue by doctor') }}</h2>
                <table class="table-clean">
                    <thead><tr><th>{{ __('Doctor') }}</th><th class="text-center">{{ __('Visits') }}</th><th class="text-end">{{ __('Revenue') }}</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($byDoctor as $row)
                            <tr><td><span class="me-2 inline-block size-2.5 rounded-full" style="background: {{ $row['color'] }}"></span>{{ $row['doctor'] }}</td><td class="text-center">{{ Format::number($row['visits']) }}</td><td class="text-end font-semibold">{{ money($row['revenue']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 font-semibold text-ink">{{ __('Revenue by service') }}</h2>
                <table class="table-clean">
                    <thead><tr><th>{{ __('Service') }}</th><th class="text-center">{{ __('Quantity') }}</th><th class="text-end">{{ __('Total') }}</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($byService as $row)
                            <tr><td>{{ $row['name'] }}</td><td class="text-center">{{ Format::number($row['count']) }}</td><td class="text-end font-semibold">{{ money($row['total']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-muted">{{ __('No invoices in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        @if ($incomeVsExpenses->isNotEmpty())
            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <section class="card p-5 lg:col-span-2">
                    <h2 class="mb-4 font-semibold text-ink">{{ __('Income vs expenses') }}</h2>
                    <div class="h-72" wire:key="ie-{{ $chartKey }}"><canvas x-data="chart(@js($ieChart))"></canvas></div>
                </section>
                <section class="card p-5">
                    <h2 class="mb-4 font-semibold text-ink">{{ __('Expenses by category') }}</h2>
                    @forelse ($expensesByCategory as $row)
                        <div class="flex justify-between border-b border-line py-2 text-sm last:border-0"><span>{{ $row['name'] }}</span><span class="font-semibold">{{ money($row['total']) }}</span></div>
                    @empty
                        <p class="text-sm text-muted">{{ __('No expenses in this period.') }}</p>
                    @endforelse
                </section>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="card p-5">
                <h2 class="mb-4 font-semibold text-ink">{{ __('Appointments by status') }}</h2>
                <div class="space-y-2">
                    @php $totalAppts = max(1, $byStatus->sum()); @endphp
                    @foreach ($statuses as $value => $status)
                        @php $count = $byStatus[$value] ?? 0; @endphp
                        <div>
                            <div class="flex justify-between text-sm"><span>{{ $status['label'] }}</span><span class="font-semibold">{{ Format::number($count) }}</span></div>
                            <div class="mt-1 h-1.5 rounded-full bg-stone-100"><div class="h-1.5 rounded-full bg-primary-500" style="width: {{ $count / $totalAppts * 100 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 font-semibold text-ink">{{ __('No-shows by doctor') }}</h2>
                <table class="table-clean">
                    <thead><tr><th>{{ __('Doctor') }}</th><th class="text-center">{{ __('Attended') }}</th><th class="text-center">{{ __('No-show') }}</th><th class="text-end">%</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($noShows as $row)
                            <tr><td>{{ $row['doctor'] }}</td><td class="text-center">{{ Format::number($row['completed']) }}</td><td class="text-center">{{ Format::number($row['no_shows']) }}</td><td class="text-end font-semibold {{ $row['rate'] > 15 ? 'text-rose-600' : '' }}">{{ Format::number($row['rate'], 1) }}%</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</div>
