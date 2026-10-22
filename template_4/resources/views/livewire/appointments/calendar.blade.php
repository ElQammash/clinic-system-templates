@php
    use App\Enums\AppointmentStatus;
    use App\Support\Format;
    use Carbon\CarbonImmutable;

    /*
     * Luxury Minimal calendar: a ledger. One hairline row per hour, one column per doctor (day view)
     * or per day (week view). Entries are quiet text lines with a status dot; an hour with a free slot
     * shows a discreet "Available" link that books the first free time in that hour.
     */
    $hhmm = fn (int $m) => sprintf('%02d:%02d', intdiv($m, 60) % 24, $m % 60);
    $columns = $grid['columns'];
    $hours = range(intdiv($grid['start'], 60), max(intdiv($grid['start'], 60), intdiv($grid['end'] - 1, 60)));

    $firstFree = function (array $column, int $hour) use ($canBook) {
        if (! $canBook) {
            return null;
        }
        $day = CarbonImmutable::parse($column['date']);
        $busy = collect($column['events'])->filter(fn ($e) => $e['appointment']->isActive())->map(fn ($e) => [$e['from'], $e['to']])
            ->merge(collect($column['blocked'])->map(fn ($b) => [$b['from'], $b['to']]));
        foreach ($column['windows'] as $window) {
            for ($m = max($window['from'], $hour * 60); $m < min($window['to'], ($hour + 1) * 60) && $m + $column['slot'] <= $window['to']; $m += $column['slot']) {
                if ((($m - $window['from']) % $column['slot']) === 0 && $day->addMinutes($m)->isFuture() && $busy->every(fn ($b) => $m + $column['slot'] <= $b[0] || $m >= $b[1])) {
                    return $day->addMinutes($m);
                }
            }
        }

        return null;
    };
    $dot = ['booked' => 'bg-neutral-400', 'confirmed' => 'bg-sky-600', 'arrived' => 'bg-amber-500', 'in_progress' => 'bg-violet-600', 'completed' => 'bg-emerald-600', 'cancelled' => 'bg-neutral-300', 'no_show' => 'bg-orange-500'];
    $weekStart = CarbonImmutable::parse($date)->startOfWeek(CarbonImmutable::SATURDAY);
@endphp
<div>
    <x-page-header :title="__('Appointments')" :subtitle="$rangeLabel">
        <x-slot:actions>
            @if ($canBook)
                <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $doctorId ?: 'null' }} })" class="btn btn-primary">{{ __('Book appointment') }}</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Controls --}}
    <div class="mb-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex items-end gap-1">
            <button type="button" wire:click="previous" class="p-2 text-neutral-400 hover:text-ink" aria-label="{{ __('Previous') }}"><x-icon name="chevron-right" class="size-4 ltr:rotate-180" /></button>
            @for ($i = 0; $i < 7; $i++)
                @php $d = $weekStart->addDays($i); $selected = $d->isSameDay($date); @endphp
                <button type="button" wire:click="$set('date', '{{ $d->toDateString() }}')" @class(['flex w-12 flex-col items-center border-b pb-2 transition', 'border-primary-500 text-ink' => $selected, 'border-transparent text-neutral-400 hover:text-ink' => ! $selected])>
                    <span class="text-[10px]">{{ $d->translatedFormat('D') }}</span>
                    <span @class(['font-serif text-2xl', 'text-primary-600' => $d->isToday() && ! $selected])>{{ $d->format('j') }}</span>
                </button>
            @endfor
            <button type="button" wire:click="next" class="p-2 text-neutral-400 hover:text-ink" aria-label="{{ __('Next') }}"><x-icon name="chevron-left" class="size-4 ltr:rotate-180" /></button>
            <button type="button" wire:click="today" class="eyebrow ms-4 pb-3 text-neutral-500 hover:text-ink">{{ __('Today') }}</button>
        </div>

        <div class="flex flex-wrap items-center gap-6 text-[13px]">
            @foreach (['day' => __('Day'), 'week' => __('Week')] as $key => $label)
                <button type="button" wire:click="setView('{{ $key }}')" @class(['border-b pb-1 transition', 'border-ink text-ink' => $view === $key, 'border-transparent text-neutral-400 hover:text-ink' => $view !== $key])>{{ $label }}</button>
            @endforeach
            @if ($this->doctors->count() > 1)
                <span class="h-4 w-px bg-line"></span>
                @if ($view === 'day')
                    <button type="button" wire:click="selectDoctor(0)" @class(['border-b pb-1', 'border-ink text-ink' => ! $doctorId, 'border-transparent text-neutral-400 hover:text-ink' => $doctorId])>{{ __('All doctors') }}</button>
                @endif
                @foreach ($this->doctors as $doc)
                    <button type="button" wire:click="selectDoctor({{ $doc->id }})" @class(['border-b pb-1', 'border-ink text-ink' => $doctorId === $doc->id, 'border-transparent text-neutral-400 hover:text-ink' => $doctorId !== $doc->id])>{{ $doc->displayName() }}</button>
                @endforeach
            @endif
        </div>
    </div>

    @if (empty($columns))
        <p class="border-y border-line py-20 text-center font-serif text-2xl text-muted italic">{{ __('No doctors yet. Add a doctor from Users.') }}</p>
    @else
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full min-w-[640px] border-collapse">
                <thead>
                    <tr class="border-b border-ink">
                        <th class="w-20"></th>
                        @foreach ($columns as $column)
                            <th @class(['px-4 pb-4 text-start align-bottom font-normal', 'bg-primary-50/60' => $column['is_today'] && $view === 'week'])>
                                <span class="block font-serif text-xl text-ink">{{ $column['label'] }}</span>
                                <span class="mt-1 block text-xs text-muted">{{ $column['sublabel'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hours as $hour)
                        <tr class="border-b border-line align-top">
                            <th class="py-4 pe-4 text-start font-serif text-lg font-normal text-primary-600 ltr-nums">{{ $hhmm($hour * 60) }}</th>
                            @foreach ($columns as $column)
                                @php
                                    $entries = collect($column['events'])->filter(fn ($e) => intdiv($e['from'], 60) === $hour);
                                    $blocked = collect($column['blocked'])->first(fn ($b) => $b['from'] < ($hour + 1) * 60 && $b['to'] > $hour * 60);
                                    $free = $blocked ? null : $firstFree($column, $hour);
                                @endphp
                                <td @class(['px-4 py-3', 'bg-primary-50/40' => $column['is_today'] && $view === 'week'])>
                                    @if ($blocked)
                                        <p class="text-xs text-rose-700 italic">{{ $blocked['reason'] ?: __('Not available') }}</p>
                                    @endif
                                    @foreach ($entries as $event)
                                        @php $a = $event['appointment']; @endphp
                                        <button type="button" wire:key="appt-{{ $a->id }}" x-on:click="$dispatch('show-appointment', { id: {{ $a->id }} })"
                                                @class(['group flex w-full items-baseline gap-2 py-1 text-start', 'opacity-40' => in_array($a->status, [AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true)])>
                                            <span class="size-1.5 shrink-0 translate-y-[-2px] rounded-full {{ $dot[$a->status->value] }}" title="{{ $a->status->label() }}"></span>
                                            <span class="w-10 shrink-0 text-xs text-muted ltr-nums">{{ $a->starts_at->format('H:i') }}</span>
                                            <span class="min-w-0">
                                                <span @class(['block truncate text-[14px] text-ink group-hover:text-primary-700', 'line-through' => $a->status === AppointmentStatus::Cancelled])>{{ $a->patient->name }}</span>
                                                <span class="block truncate text-[11px] text-muted">{{ $a->visitType?->name() }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                    @if ($free)
                                        <button type="button" x-on:click="$dispatch('book-appointment', { doctorId: {{ $column['doctor_id'] }}, start: '{{ $free->format('Y-m-d H:i') }}' })"
                                                class="mt-1 text-[11px] text-neutral-400 underline-offset-4 transition hover:text-primary-700 hover:underline">
                                            {{ __('Available') }} · <span class="ltr-nums">{{ $free->format('H:i') }}</span>
                                        </button>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex flex-wrap gap-6 text-xs text-muted">
            @foreach (AppointmentStatus::cases() as $status)
                <span class="flex items-center gap-2"><span class="size-1.5 rounded-full {{ $dot[$status->value] }}"></span>{{ $status->label() }}</span>
            @endforeach
        </div>
    @endif

    <livewire:appointments.booking-form />
    <livewire:appointments.appointment-details />
    <livewire:patients.patient-editor :redirect-on-create="false" />
</div>
