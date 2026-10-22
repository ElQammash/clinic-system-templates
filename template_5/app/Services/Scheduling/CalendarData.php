<?php

namespace App\Services\Scheduling;

use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds a template-agnostic calendar model: columns (one per doctor for a day,
 * or one per day for a doctor's week), their working windows, blocked periods
 * and appointments, each with its position expressed in minutes from the grid start.
 * Every template turns those minutes into pixels / rows in its own way.
 */
class CalendarData
{
    public function __construct(protected Availability $availability) {}

    /**
     * @param  Collection<int, User>  $doctors
     * @return array{start: int, end: int, columns: list<array<string, mixed>>}
     */
    public function day(CarbonImmutable $date, Collection $doctors, User $viewer): array
    {
        $columns = $doctors->map(fn (User $doctor) => $this->column($doctor, $date, $doctor->displayName(), $doctor->doctorProfile?->title(), $viewer))->all();

        return $this->withBounds($columns);
    }

    /** @return array{start: int, end: int, columns: list<array<string, mixed>>} */
    public function week(CarbonImmutable $weekStart, User $doctor, User $viewer): array
    {
        $columns = collect(range(0, 6))
            ->map(fn (int $i) => $weekStart->addDays($i))
            ->map(fn (CarbonImmutable $day) => $this->column($doctor, $day, $day->translatedFormat('l'), $day->translatedFormat('j M'), $viewer))
            ->all();

        return $this->withBounds($columns);
    }

    /** @return array<string, mixed> */
    protected function column(User $doctor, CarbonImmutable $date, string $label, ?string $sublabel, User $viewer): array
    {
        $dayStart = $date->startOfDay();
        $minutes = fn ($time) => (int) $dayStart->diffInMinutes($time, false);

        $windows = $this->availability->windows($doctor, $date)
            ->map(fn (array $w) => ['from' => $minutes($w[0]), 'to' => $minutes($w[1])])
            ->values()->all();

        $blocked = BlockedPeriod::query()->overlapping($doctor->id, $dayStart, $dayStart->addDay())->get()
            ->map(fn (BlockedPeriod $b) => [
                'from' => max(0, $minutes($b->starts_at)),
                'to' => min(24 * 60, $minutes($b->ends_at)),
                'reason' => $b->reason,
            ])->values()->all();

        $appointments = Appointment::query()
            ->with(['patient', 'visitType'])
            ->where('doctor_id', $doctor->id)
            ->onDate($date)
            ->visibleTo($viewer)
            ->orderBy('starts_at')
            ->get();

        return [
            'key' => $doctor->id.'-'.$date->toDateString(),
            'doctor_id' => $doctor->id,
            'date' => $date->toDateString(),
            'label' => $label,
            'sublabel' => $sublabel,
            'color' => $doctor->doctorProfile?->color ?? '#0d9488',
            'is_today' => $date->isToday(),
            'slot' => $doctor->doctorProfile?->slot_minutes ?? 15,
            'windows' => $windows,
            'blocked' => $blocked,
            'events' => $this->layoutEvents($appointments, $minutes),
        ];
    }

    /**
     * Overlapping appointments (e.g. a walk-in squeezed between bookings) are placed side by side.
     *
     * @return list<array{appointment: Appointment, from: int, to: int, lane: int, lanes: int}>
     */
    protected function layoutEvents(Collection $appointments, callable $minutes): array
    {
        $events = [];
        $laneEnds = [];

        foreach ($appointments as $appointment) {
            $from = $minutes($appointment->starts_at);
            $to = max($from + 10, $minutes($appointment->ends_at));

            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $from) {
                $lane++;
            }
            $laneEnds[$lane] = $to;

            $events[] = ['appointment' => $appointment, 'from' => $from, 'to' => $to, 'lane' => $lane, 'lanes' => 1];
        }

        // Each event spans the number of lanes in use while it is on screen.
        foreach ($events as $i => $event) {
            $overlapping = array_filter($events, fn ($other) => $other['from'] < $event['to'] && $other['to'] > $event['from']);
            $events[$i]['lanes'] = max(array_column($overlapping, 'lane')) + 1;
        }

        return $events;
    }

    /** Visible hour range: covers every working window and appointment, rounded to whole hours. */
    protected function withBounds(array $columns): array
    {
        $starts = [];
        $ends = [];

        foreach ($columns as $column) {
            foreach ($column['windows'] as $w) {
                $starts[] = $w['from'];
                $ends[] = $w['to'];
            }
            foreach ($column['events'] as $e) {
                $starts[] = $e['from'];
                $ends[] = $e['to'];
            }
        }

        $start = $starts ? intdiv(min($starts), 60) * 60 : 9 * 60;
        $end = $ends ? (int) ceil(max($ends) / 60) * 60 : 17 * 60;

        return ['start' => $start, 'end' => max($end, $start + 60), 'columns' => $columns];
    }
}
