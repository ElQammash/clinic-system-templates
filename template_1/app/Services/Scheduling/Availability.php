<?php

namespace App\Services\Scheduling;

use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\User;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Answers "when can this doctor see patients?": working windows, blocked periods,
 * free slots and conflicts. Pure reads — booking itself lives in BookAppointment.
 */
class Availability
{
    /**
     * Working windows of a doctor on a date. Doctors without their own hours use the clinic defaults.
     *
     * @return Collection<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public function windows(User $doctor, CarbonInterface $date): Collection
    {
        $date = CarbonImmutable::parse($date)->startOfDay();

        $hasOwnHours = WorkingHour::query()->where('doctor_id', $doctor->id)->exists();

        return WorkingHour::query()
            ->when($hasOwnHours, fn ($q) => $q->where('doctor_id', $doctor->id), fn ($q) => $q->whereNull('doctor_id'))
            ->where('day_of_week', $date->dayOfWeek)
            ->orderBy('starts_at')
            ->get()
            ->map(function (WorkingHour $hour) use ($date) {
                $start = $date->setTimeFromTimeString($hour->starts_at);
                $end = $date->setTimeFromTimeString($hour->ends_at);

                // Shifts that end after midnight (e.g. 20:00 – 01:00).
                if ($end->lessThanOrEqualTo($start)) {
                    $end = $end->addDay();
                }

                return [$start, $end];
            })
            ->values();
    }

    public function isWithinWorkingHours(User $doctor, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $this->windows($doctor, $start)->contains(
            fn (array $window) => $start->greaterThanOrEqualTo($window[0]) && $end->lessThanOrEqualTo($window[1])
        );
    }

    public function blockingPeriod(User $doctor, CarbonInterface $start, CarbonInterface $end): ?BlockedPeriod
    {
        return BlockedPeriod::query()->overlapping($doctor->id, $start, $end)->first();
    }

    /** Active appointments of the doctor that overlap [start, end). */
    public function conflictsFor(int $doctorId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): Collection
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->active()
            ->between($start, $end)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get();
    }

    /** Active appointments of the patient (with any doctor) that overlap [start, end). */
    public function patientConflicts(int $patientId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): Collection
    {
        return Appointment::query()
            ->where('patient_id', $patientId)
            ->active()
            ->between($start, $end)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get();
    }

    /**
     * Free start times for a doctor on a date, stepping by the doctor's slot length.
     *
     * @return list<CarbonImmutable>
     */
    public function freeSlots(User $doctor, CarbonInterface $date, int $duration, ?int $ignoreAppointmentId = null): array
    {
        $step = max(5, $doctor->doctorProfile?->slot_minutes ?? 15);
        $now = CarbonImmutable::now();

        $dayStart = CarbonImmutable::parse($date)->startOfDay();
        $dayEnd = $dayStart->addDay()->addHours(6); // covers shifts that run past midnight

        $taken = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->active()
            ->between($dayStart, $dayEnd)
            ->when($ignoreAppointmentId, fn ($q) => $q->whereKeyNot($ignoreAppointmentId))
            ->get(['starts_at', 'ends_at']);

        $blocked = BlockedPeriod::query()->overlapping($doctor->id, $dayStart, $dayEnd)->get(['starts_at', 'ends_at']);

        $slots = [];

        foreach ($this->windows($doctor, $dayStart) as [$windowStart, $windowEnd]) {
            for ($slot = $windowStart; $slot->addMinutes($duration)->lessThanOrEqualTo($windowEnd); $slot = $slot->addMinutes($step)) {
                $slotEnd = $slot->addMinutes($duration);

                if ($slot->lessThan($now)) {
                    continue;
                }

                $overlaps = fn ($item) => $item->starts_at->lessThan($slotEnd) && $item->ends_at->greaterThan($slot);

                if ($taken->contains($overlaps) || $blocked->contains($overlaps)) {
                    continue;
                }

                $slots[] = $slot;
            }
        }

        return $slots;
    }
}
