@php
    use App\Support\Format;

    $hour = now()->hour;
    [$greeting, $greetEmoji] = $hour < 12 ? [__('Good morning'), 'sun'] : ($hour < 18 ? [__('Good afternoon'), 'sunset'] : [__('Good evening'), 'moon']);

    $chart = [
        'type' => 'bar',
        'data' => [
            'labels' => $revenueChart->keys()->map(fn ($d) => Format::date($d, 'j/n'))->values(),
            'datasets' => [['label' => __('Revenue'), 'data' => $revenueChart->values(), 'backgroundColor' => 'primary', 'borderRadius' => 999, 'borderSkipped' => false, 'maxBarThickness' => 12]],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8]], 'y' => ['beginAtZero' => true, 'border' => ['display' => false], 'ticks' => ['maxTicksLimit' => 4]]],
        ],
    ];

    // Pastel stat bubbles: [emoji, label, value, hint, background]
    $bubbles = array_values(array_filter([
        $seesAppointments ? ['calendar', __('Appointments today'), Format::number($today['appointments']), __(':done done · :waiting waiting', ['done' => Format::number($counts['done']), 'waiting' => Format::number($counts['waiting'])]), 'bg-primary-50'] : null,
        $seesMoney ? ['cash', __('Revenue today'), money($today['revenue']), __('Collected payments'), 'bg-emerald-50'] : null,
        $seesMoney ? ['money', __('Revenue this month'), money($month['revenue']), $isFinancial && $month['net'] !== null ? __('Net after expenses: :amount', ['amount' => money($month['net'])]) : __('Collected payments'), 'bg-amber-50'] : null,
        ! $seesMoney ? ['stethoscope', __('Visits this month'), Format::number($month['visits']), __('Completed visits'), 'bg-emerald-50'] : null,
        ['baby', __('New patients this month'), Format::number($month['new_patients']), __('No-show rate: :rate%', ['rate' => Format::number($month['no_show_rate'], 1)]), 'bg-violet-50'],
    ]));
    $avatarTones = ['bg-primary-100 text-primary-700', 'bg-amber-100 text-amber-800', 'bg-emerald-100 text-emerald-800', 'bg-sky-100 text-sky-800', 'bg-violet-100 text-violet-800'];
@endphp
<div>
    {{-- Greeting card --}}
    <section class="relative mb-6 overflow-hidden rounded-[2rem] bg-gradient-to-br from-primary-400 via-primary-500 to-secondary-400 p-6 text-white shadow-(--shadow-lift) sm:p-8">
        <span class="emoji pointer-events-none absolute -end-4 -top-6 text-[9rem] opacity-25 select-none" aria-hidden="true">{{ ['sun' => '☀️', 'sunset' => '🌇', 'moon' => '🌙'][$greetEmoji] }}</span>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[15px] font-semibold text-white/85">{{ Format::longDate(now()) }}</p>
                <h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">{{ __(':greeting, :name', ['greeting' => $greeting, 'name' => $user->displayName()]) }} <span class="emoji inline-block animate-[wave_1.8s_ease-in-out_infinite]">👋</span></h1>
                @if ($seesAppointments)
                    <p class="mt-2 text-lg text-white/90">
                        {{ trans_choice('{0} No more appointments today — enjoy a coffee ☕|{1} One more patient is expected today.|[2,*] :count more patients are expected today.', $counts['remaining'], ['count' => Format::number($counts['remaining'])]) }}
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($seesAppointments && Route::has('queue'))
                    <a href="{{ route('queue') }}" wire:navigate class="btn bg-white text-primary-600 shadow-[0_3px_0_rgb(0_0_0/0.12)] hover:bg-cream"><x-emoji name="queue" /> {{ __('Waiting room') }}</a>
                @endif
                @can('create', App\Models\Appointment::class)
                    <a href="{{ route('appointments.index') }}" wire:navigate class="btn bg-white/20 text-white ring-2 ring-white/50 hover:bg-white/30"><x-emoji name="calendar" /> {{ __('Appointments') }}</a>
                @endcan
            </div>
        </div>
    </section>

    {{-- Stat bubbles --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($bubbles as [$emoji, $label, $value, $hint, $bg])
            <div class="card flex items-center gap-4 p-5">
                <x-emoji :name="$emoji" class="grid size-16 shrink-0 place-items-center rounded-3xl text-3xl {{ $bg }}" />
                <div class="min-w-0">
                    <p class="text-sm font-bold text-muted">{{ $label }}</p>
                    <p class="text-2xl leading-tight font-extrabold text-ink ltr-nums">{{ $value }}</p>
                    <p class="truncate text-[13px] text-muted">{{ $hint }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        {{-- Today's patients --}}
        @if ($seesAppointments)
            <section class="card p-5 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xl font-extrabold"><x-emoji name="patients" /> {{ __('Today\'s schedule') }}</h2>
                    <span class="rounded-full bg-cream px-3 py-1 text-sm font-bold text-muted">{{ Format::number($counts['remaining']) }} {{ __('Expected') }}</span>
                </div>
                <div class="mb-4 grid grid-cols-4 gap-2 text-center">
                    @foreach (['remaining' => [__('Expected'), 'bg-sky-50 text-sky-800'], 'waiting' => [__('Waiting'), 'bg-amber-50 text-amber-800'], 'with_doctor' => [__('With doctor'), 'bg-violet-50 text-violet-800'], 'done' => [__('Done'), 'bg-emerald-50 text-emerald-800']] as $key => [$label, $tone])
                        <div class="rounded-2xl py-2 {{ $tone }}"><p class="text-xl font-extrabold">{{ Format::number($counts[$key]) }}</p><p class="text-xs font-bold">{{ $label }}</p></div>
                    @endforeach
                </div>
                <ul class="space-y-2">
                    @forelse ($todayAppointments->filter(fn ($a) => $a->isActive())->take(8) as $a)
                        <li class="flex items-center gap-3 rounded-2xl bg-canvas p-2.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full text-sm font-extrabold {{ $avatarTones[$a->patient_id % count($avatarTones)] }}">{{ $a->patient->initials() }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-ink">{{ $a->patient->name }}</p>
                                <p class="text-[13px] text-muted"><span class="ltr-nums">{{ Format::time($a->starts_at) }}</span> · {{ $a->doctor->displayName() }}</p>
                            </div>
                            <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                        </li>
                    @empty
                        <li class="rounded-3xl bg-canvas py-10 text-center"><span class="emoji text-4xl">☕</span><p class="mt-2 font-bold text-muted">{{ __('No more appointments today.') }}</p></li>
                    @endforelse
                </ul>
            </section>
        @endif

        <div @class(['space-y-6', 'lg:col-span-3' => $seesAppointments, 'lg:col-span-5' => ! $seesAppointments])>
            @if ($seesMoney)
                <section class="card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-xl font-extrabold"><x-emoji name="chart" /> {{ __('Revenue — last 30 days') }}</h2>
                        @if (Route::has('reports.index') && App\Livewire\Reports\Index::canView($user))
                            <a href="{{ route('reports.index') }}" wire:navigate class="btn btn-ghost btn-sm text-primary-600">{{ __('Reports') }} {{ is_rtl() ? '←' : '→' }}</a>
                        @endif
                    </div>
                    <div class="h-60" wire:ignore><canvas x-data="chart(@js($chart))"></canvas></div>
                    @if ($outstanding > 0)
                        <p class="mt-3 rounded-2xl bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700"><span class="emoji">🧾</span> {{ __('Outstanding (all patients)') }}: <span class="ltr-nums">{{ money($outstanding) }}</span></p>
                    @endif
                </section>
            @endif

            @if ($byDoctor->isNotEmpty())
                <section class="card p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-xl font-extrabold"><x-emoji name="doctor" /> {{ __('Doctors this month') }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($byDoctor as $row)
                            <div class="flex items-center gap-3 rounded-3xl border-2 border-line p-3">
                                <span class="grid size-12 shrink-0 place-items-center rounded-full text-lg font-extrabold text-white" style="background: {{ $row['color'] }}">{{ mb_substr(preg_replace('/^(د\.|Dr\.?)\s*/u', '', $row['doctor']), 0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $row['doctor'] }}</p>
                                    <p class="text-[13px] text-muted">{{ trans_choice(':count visit|:count visits', $row['visits'], ['count' => Format::number($row['visits'])]) }} · <span class="font-bold text-ink ltr-nums">{{ money($row['revenue']) }}</span></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($lowStock->isNotEmpty())
                <section class="card border-amber-200 bg-amber-50/40 p-5">
                    <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold text-amber-900"><x-emoji name="box" /> {{ __('Low stock') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($lowStock as $item)
                            <span class="rounded-full bg-white px-3 py-1 text-sm font-bold shadow-(--shadow-soft)">{{ $item->name }} · <span class="text-rose-600">{{ $item->quantity }} {{ $item->unit }}</span></span>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($checklist)
                <section class="card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-xl font-extrabold"><x-emoji name="sparkles" /> {{ __('Getting started') }}</h2>
                        <span class="text-sm font-bold text-primary-600">{{ collect($checklist)->where('done', true)->count() }}/{{ count($checklist) }}</span>
                    </div>
                    <div class="mb-4 h-3 overflow-hidden rounded-full bg-cream"><div class="h-full rounded-full bg-gradient-to-l from-primary-400 to-secondary-400" style="width: {{ collect($checklist)->where('done', true)->count() / max(1, count($checklist)) * 100 }}%"></div></div>
                    <ul class="space-y-1.5">
                        @foreach ($checklist as $step)
                            <li class="flex items-center gap-3 rounded-2xl px-2 py-1.5">
                                <span class="emoji text-xl">{{ $step['done'] ? '✅' : '⬜' }}</span>
                                <span @class(['flex-1 font-semibold', 'text-muted line-through' => $step['done']])>{{ $step['label'] }}</span>
                                @if (! $step['done'] && $step['url'])<a href="{{ $step['url'] }}" wire:navigate class="btn btn-secondary btn-sm">{{ __('Set up') }}</a>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</div>
