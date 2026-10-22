<?php

namespace App\Actions\Scheduling;

use App\Models\WorkingHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveWorkingHours
{
    /**
     * Replace the weekly schedule of a doctor (or the clinic default when $doctorId is null).
     *
     * @param  array<int|string, list<array{from: ?string, to: ?string}>>  $shifts  day_of_week => shifts
     */
    public function handle(?int $doctorId, array $shifts): void
    {
        $rows = [];

        foreach ($shifts as $day => $dayShifts) {
            $clean = collect($dayShifts)
                ->filter(fn ($s) => filled($s['from'] ?? null) && filled($s['to'] ?? null))
                ->sortBy('from')
                ->values();

            foreach ($clean as $i => $shift) {
                if (! preg_match('/^\d{2}:\d{2}$/', $shift['from']) || ! preg_match('/^\d{2}:\d{2}$/', $shift['to'])) {
                    throw ValidationException::withMessages(["shifts.{$day}" => __('Please enter valid times.')]);
                }

                if ($shift['from'] === $shift['to']) {
                    throw ValidationException::withMessages(["shifts.{$day}" => __('A shift must end after it starts.')]);
                }

                // Shifts may cross midnight only as the last shift of the day.
                $previous = $clean[$i - 1] ?? null;
                if ($previous && $previous['to'] > $shift['from']) {
                    throw ValidationException::withMessages(["shifts.{$day}" => __('Shifts on the same day must not overlap.')]);
                }

                $rows[] = ['doctor_id' => $doctorId, 'day_of_week' => (int) $day, 'starts_at' => $shift['from'], 'ends_at' => $shift['to']];
            }
        }

        DB::transaction(function () use ($doctorId, $rows) {
            WorkingHour::query()->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId), fn ($q) => $q->whereNull('doctor_id'))->delete();

            foreach ($rows as $row) {
                WorkingHour::create($row);
            }
        });

        activity('settings')->event('updated')->withProperties(['working_hours' => $doctorId ?? 'clinic', 'shifts' => count($rows)])->log('working hours updated');
    }

    /** Doctor goes back to the clinic's default hours. */
    public function useClinicDefaults(int $doctorId): void
    {
        WorkingHour::query()->where('doctor_id', $doctorId)->delete();
    }
}
