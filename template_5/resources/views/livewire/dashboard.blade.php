@php
    use App\Support\Format;

    $active = $todayAppointments->filter(fn ($a) => $a->isActive());
    $next = $active->first(fn ($a) => in_array($a->status->value, ['booked', 'confirmed'], true));

    $chart = [
        'type' => 'bar',
        'data' => [
            'labels' => $revenueChart->keys()->map(fn ($d) => Format::date($d, 'j/n'))->values(),
            'datasets' => [['label' => __('Revenue'), 'data' => $revenueChart->values(), 'backgroundColor' => 'primary', 'borderRadius' => 6, 'maxBarThickness' => 18]],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8, 'font' => ['size' => 13]]], 'y' => ['beginAtZero' => true, 'ticks' => ['maxTicksLimit' => 4, 'font' => ['size' => 13]]]],
        ],
    ];

    // Big reception actions (only the ones this user may use).
    $actions = array_values(array_filter([
        $seesAppointments && Route::has('queue') ? [route('queue'), 'queue', __('Waiting room'), __('Check in & call patients'), 'bg-primary-600 text-white'] : null,
        $seesAppointments && auth()->user()->can('create', App\Models\Appointment::class) ? [route('appointments.index'), 'calendar', __('Book appointment'), __('Find a free time'), 'bg-secondary-600 text-white'] : null,
        auth()->user()->can('create', App\Models\Patient::class) ? [route('patients.index'), 'user-plus', __('Patients'), __('Search or add a patient'), 'bg-white text-ink border-2 border-line'] : null,
        auth()->user()->can('create', App\Models\Invoice::class) ? [route('invoices.create'), 'receipt', __('New invoice'), __('Bill & take payment'), 'bg-white text-ink border-2 border-line'] : null,
    ]));
@endphp
<div>
    <h1 class="mb-5 text-3xl font-extrabold">{{ __(':greeting, :name', ['greeting' => now()->hour < 12 ? __('Good morning') : (now()->hour < 18 ? __('Good afternoon') : __('Good evening')), 'name' => $user->displayName()]) }}</h1>

    {{-- Action tiles --}}
    @if ($actions)
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($actions as [$url, $icon, $label, $hint, $tone])
                <a href="{{ $url }}" wire:navigate class="flex min-h-36 flex-col justify-between rounded-3xl p-5 shadow-(--shadow-soft) transition active:scale-[0.98] {{ $tone }}">
                    <x-icon :name="$icon" class="size-10" />
                    <span>
                        <span class="block text-xl leading-tight font-extrabold">{{ $label }}</span>
                        <span class="mt-1 block text-[15px] font-semibold opacity-80">{{ $hint }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_28rem]">
        <div class="min-w-0 space-y-6">
            @if ($seesAppointments)
                {{-- Status strip --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (['remaining' => [__('Expected'), 'border-sky-600 text-sky-800'], 'waiting' => [__('Waiting'), 'border-amber-500 text-amber-800'], 'with_doctor' => [__('With doctor'), 'border-violet-600 text-violet-800'], 'done' => [__('Done'), 'border-emerald-600 text-emerald-800']] as $key => [$label, $tone])
                        <div class="rounded-3xl border-s-8 bg-white px-5 py-4 shadow-(--shadow-soft) {{ $tone }}">
                            <p class="text-4xl leading-none font-extrabold">{{ Format::number($counts[$key]) }}</p>
                            <p class="mt-1 text-base font-bold">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Today --}}
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b-2 border-line px-5 py-4">
                        <h2 class="text-2xl font-extrabold">{{ __('Today\'s schedule') }}</h2>
                        <a href="{{ route('appointments.index') }}" wire:navigate class="btn btn-secondary btn-sm">{{ __('Appointments') }}</a>
                    </div>
                    <ul class="divide-y-2 divide-line">
                        @forelse ($active->take(10) as $a)
                            <li class="flex min-h-18 items-center gap-4 px-5 py-3">
                                <span class="w-20 shrink-0 text-xl font-extrabold text-primary-700 ltr-nums">{{ $a->starts_at->format('H:i') }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-lg font-bold">{{ $a->patient->name }}</span>
                                    <span class="block truncate text-[15px] text-muted">{{ $a->doctor->displayName() }} · {{ $a->visitType?->name() }}</span>
                                </span>
                                <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                            </li>
                        @empty
                            <li class="px-5 py-14 text-center text-lg font-bold text-muted">{{ __('No more appointments today.') }}</li>
                        @endforelse
                    </ul>
                </section>
            @endif

            @if ($seesMoney)
                <section class="card p-5">
                    <h2 class="mb-4 text-2xl font-extrabold">{{ __('Revenue — last 30 days') }}</h2>
                    <div class="h-64" wire:ignore><canvas x-data="chart(@js($chart))"></canvas></div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            @if ($seesAppointments)
                <section class="rounded-[1.75rem] bg-ink p-6 text-white shadow-(--shadow-lift)">
                    <p class="text-base font-bold text-primary-300">{{ __('Next up') }}</p>
                    @if ($next)
                        <p class="mt-2 text-5xl font-extrabold ltr-nums">{{ $next->starts_at->format('H:i') }}</p>
                        <p class="mt-3 text-2xl font-bold">{{ $next->patient->name }}</p>
                        <p class="text-lg text-white/70">{{ $next->doctor->displayName() }} · {{ $next->visitType?->name() }}</p>
                        @if ($next->patient->phone)<p class="mt-1 text-lg text-white/70 ltr-nums">{{ $next->patient->phone }}</p>@endif
                        <a href="{{ route('queue') }}" wire:navigate class="btn btn-lg mt-5 w-full bg-white text-ink hover:bg-primary-50">{{ __('Check in') }}</a>
                    @else
                        <p class="mt-3 text-2xl font-bold">{{ __('No more appointments today.') }}</p>
                    @endif
                </section>
            @endif

            <section class="grid grid-cols-2 gap-3">
                @if ($seesMoney)
                    <div class="card p-4"><p class="text-[15px] font-bold text-muted">{{ __('Revenue today') }}</p><p class="mt-1 text-2xl font-extrabold ltr-nums">{{ money($today['revenue']) }}</p></div>
                    <div class="card p-4"><p class="text-[15px] font-bold text-muted">{{ __('Revenue this month') }}</p><p class="mt-1 text-2xl font-extrabold ltr-nums">{{ money($month['revenue']) }}</p></div>
                    <div class="card col-span-2 p-4"><p class="text-[15px] font-bold text-muted">{{ __('Outstanding (all patients)') }}</p><p class="mt-1 text-2xl font-extrabold text-rose-700 ltr-nums">{{ money($outstanding) }}</p></div>
                @else
                    <div class="card p-4"><p class="text-[15px] font-bold text-muted">{{ __('Visits this month') }}</p><p class="mt-1 text-2xl font-extrabold">{{ Format::number($month['visits']) }}</p></div>
                @endif
                <div class="card p-4"><p class="text-[15px] font-bold text-muted">{{ __('New patients this month') }}</p><p class="mt-1 text-2xl font-extrabold">{{ Format::number($month['new_patients']) }}</p></div>
                @if ($seesMoney && $isFinancial && $month['net'] !== null)
                    <div class="card p-4"><p class="text-[15px] font-bold text-muted">{{ __('Net') }}</p><p class="mt-1 text-2xl font-extrabold ltr-nums">{{ money($month['net']) }}</p></div>
                @endif
            </section>

            @if ($byDoctor->isNotEmpty())
                <section class="card p-5">
                    <h2 class="mb-3 text-xl font-extrabold">{{ __('Doctors this month') }}</h2>
                    <ul class="space-y-3">
                        @foreach ($byDoctor as $row)
                            <li class="flex items-center gap-3"><span class="size-4 shrink-0 rounded" style="background: {{ $row['color'] }}"></span><span class="min-w-0 flex-1 truncate text-lg font-bold">{{ $row['doctor'] }}</span><span class="text-lg font-extrabold ltr-nums">{{ money($row['revenue']) }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($lowStock->isNotEmpty())
                <section class="card border-amber-400 p-5">
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold text-amber-900"><x-icon name="warning" class="size-6" /> {{ __('Low stock') }}</h2>
                    <ul class="space-y-2 text-lg">
                        @foreach ($lowStock as $item)
                            <li class="flex justify-between"><span>{{ $item->name }}</span><span class="font-extrabold text-rose-700">{{ $item->quantity }} {{ $item->unit }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($checklist)
                <section class="card p-5">
                    <h2 class="mb-3 text-xl font-extrabold">{{ __('Getting started') }} <span class="text-primary-700">{{ collect($checklist)->where('done', true)->count() }}/{{ count($checklist) }}</span></h2>
                    <ul class="space-y-2">
                        @foreach ($checklist as $step)
                            <li class="flex min-h-12 items-center gap-3">
                                <span @class(['grid size-8 shrink-0 place-items-center rounded-xl', 'bg-emerald-600 text-white' => $step['done'], 'border-2 border-slate-300' => ! $step['done']])>@if ($step['done'])<x-icon name="check" class="size-5" />@endif</span>
                                <span @class(['flex-1 text-base font-semibold', 'text-muted line-through' => $step['done']])>{{ $step['label'] }}</span>
                                @if (! $step['done'] && $step['url'])<a href="{{ $step['url'] }}" wire:navigate class="btn btn-secondary btn-sm">{{ __('Set up') }}</a>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>
