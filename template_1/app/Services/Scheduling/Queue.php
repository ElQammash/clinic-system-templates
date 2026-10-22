<?php

namespace App\Services\Scheduling;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Today's waiting room: queue numbers, walk-ins and "call next patient".
 */
class Queue
{
    /** Next queue number for a doctor on a day (numbers are per doctor, per day). */
    public function nextNumber(int $doctorId, CarbonInterface $day): int
    {
        return (int) Appointment::query()
            ->where('doctor_id', $doctorId)
            ->onDate($day)
            ->max('queue_number') + 1;
    }

    /**
     * Today's appointments grouped for the waiting room screen.
     *
     * @return array{waiting: Collection, in_room: Collection, upcoming: Collection, done: Collection}
     */
    public function today(User $viewer, ?int $doctorId = null): array
    {
        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'visitType'])
            ->onDate(today())
            ->visibleTo($viewer)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('starts_at')
            ->get();

        return [
            'waiting' => $appointments->where('status', AppointmentStatus::Arrived)->sortBy('queue_number')->values(),
            'in_room' => $appointments->where('status', AppointmentStatus::InProgress)->values(),
            'upcoming' => $appointments->whereIn('status', [AppointmentStatus::Booked, AppointmentStatus::Confirmed])->values(),
            'done' => $appointments->whereIn('status', [AppointmentStatus::Completed, AppointmentStatus::NoShow, AppointmentStatus::Cancelled])->sortByDesc('updated_at')->values(),
        ];
    }

    /** The next patient for the doctor: lowest queue number among those waiting. */
    public function next(int $doctorId): ?Appointment
    {
        return Appointment::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->onDate(today())
            ->where('status', AppointmentStatus::Arrived)
            ->orderBy('queue_number')
            ->orderBy('checked_in_at')
            ->first();
    }

    /** Register a patient who arrived without an appointment: straight into the queue. */
    public function walkIn(Patient $patient, User $doctor, ?VisitType $visitType, User $actor, ?string $notes = null): Appointment
    {
        if (! $doctor->isDoctor() || ! $doctor->is_active) {
            throw ValidationException::withMessages(['doctor_id' => __('Please choose an active doctor.')]);
        }

        $alreadyQueued = Appointment::query()->where('patient_id', $patient->id)->onDate(today())
            ->whereIn('status', [AppointmentStatus::Arrived, AppointmentStatus::InProgress])->exists();

        if ($alreadyQueued) {
            throw ValidationException::withMessages(['patient_id' => __('This patient is already in the waiting room.')]);
        }

        return DB::transaction(function () use ($patient, $doctor, $visitType, $actor, $notes) {
            User::query()->whereKey($doctor->id)->lockForUpdate()->first();

            $duration = $visitType?->durationFor($doctor) ?? ($doctor->doctorProfile?->slot_minutes ?? 15);

            return Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'visit_type_id' => $visitType?->id,
                'starts_at' => now()->seconds(0),
                'ends_at' => now()->seconds(0)->addMinutes($duration),
                'status' => AppointmentStatus::Arrived,
                'source' => AppointmentSource::WalkIn,
                'checked_in_at' => now(),
                'queue_number' => $this->nextNumber($doctor->id, now()),
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);
        });
    }
}
