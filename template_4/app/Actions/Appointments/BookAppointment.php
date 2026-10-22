<?php

namespace App\Actions\Appointments;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\VisitType;
use App\Services\Scheduling\Availability;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books a new appointment or reschedules an existing one, guaranteeing that
 * a doctor is never double-booked. The doctor's row is locked for the duration
 * of the transaction so two receptionists booking the same slot at the same
 * moment are serialised: the second one gets a validation error.
 */
class BookAppointment
{
    public function __construct(protected Availability $availability) {}

    /**
     * @param  array{patient_id: int, doctor_id: int, visit_type_id?: ?int, starts_at: string, duration?: ?int, source?: ?string, notes?: ?string, outside_hours?: bool}  $data
     */
    public function handle(array $data, User $actor, ?Appointment $appointment = null): Appointment
    {
        $doctor = User::query()->with('doctorProfile')->findOrFail($data['doctor_id']);
        $patient = Patient::query()->findOrFail($data['patient_id']);
        $visitType = filled($data['visit_type_id'] ?? null) ? VisitType::findOrFail($data['visit_type_id']) : null;

        $start = CarbonImmutable::parse($data['starts_at'])->seconds(0);
        $duration = (int) ($data['duration'] ?? null) ?: ($visitType?->durationFor($doctor) ?? ($doctor->doctorProfile?->slot_minutes ?? 15));
        $end = $start->addMinutes($duration);

        $this->guardBasics($doctor, $start, $appointment);

        return DB::transaction(function () use ($data, $actor, $appointment, $doctor, $patient, $visitType, $start, $end) {
            // Serialise concurrent bookings for this doctor.
            User::query()->whereKey($doctor->id)->lockForUpdate()->first();

            $this->guardSlot($doctor, $patient, $start, $end, $appointment?->id, (bool) ($data['outside_hours'] ?? false), $actor);

            $attributes = [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'visit_type_id' => $visitType?->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'notes' => $data['notes'] ?? null,
            ];

            if ($appointment) {
                $appointment->update($attributes);

                return $appointment;
            }

            return Appointment::create($attributes + [
                'status' => AppointmentStatus::Booked,
                'source' => AppointmentSource::tryFrom($data['source'] ?? '') ?? AppointmentSource::Booked,
                'created_by' => $actor->id,
            ]);
        });
    }

    protected function guardBasics(User $doctor, CarbonImmutable $start, ?Appointment $appointment): void
    {
        if (! $doctor->isDoctor() || ! $doctor->is_active) {
            throw ValidationException::withMessages(['doctor_id' => __('Please choose an active doctor.')]);
        }

        if ($appointment && ! $appointment->isActive()) {
            throw ValidationException::withMessages(['starts_at' => __('Only active appointments can be rescheduled.')]);
        }

        if ($start->lessThan(now()->subMinutes(5))) {
            throw ValidationException::withMessages(['starts_at' => __('The appointment time is in the past.')]);
        }
    }

    protected function guardSlot(User $doctor, Patient $patient, CarbonImmutable $start, CarbonImmutable $end, ?int $ignoreId, bool $outsideHours, User $actor): void
    {
        if ($block = $this->availability->blockingPeriod($doctor, $start, $end)) {
            throw ValidationException::withMessages(['starts_at' => $block->reason
                ? __('The doctor is not available at this time (:reason).', ['reason' => $block->reason])
                : __('The doctor is not available at this time.')]);
        }

        // Booking outside working hours is allowed only for users who manage schedules, and only explicitly.
        $mayOverride = $outsideHours && $actor->can('schedule.manage');
        if (! $mayOverride && ! $this->availability->isWithinWorkingHours($doctor, $start, $end)) {
            throw ValidationException::withMessages(['starts_at' => __('This time is outside the doctor\'s working hours.')]);
        }

        $conflict = $this->availability->conflictsFor($doctor->id, $start, $end, $ignoreId)->first();
        if ($conflict) {
            throw ValidationException::withMessages(['starts_at' => __('The doctor already has an appointment from :from to :to.', [
                'from' => Format::time($conflict->starts_at),
                'to' => Format::time($conflict->ends_at),
            ])]);
        }

        if ($this->availability->patientConflicts($patient->id, $start, $end, $ignoreId)->isNotEmpty()) {
            throw ValidationException::withMessages(['patient_id' => __('This patient already has another appointment at the same time.')]);
        }
    }
}
