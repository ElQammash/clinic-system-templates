<?php

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Scheduling\Availability;
use App\Services\Scheduling\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves an appointment through its life cycle (booked → confirmed → arrived →
 * in progress → completed, or cancelled / no-show) and stamps the matching time.
 */
class ChangeAppointmentStatus
{
    public function __construct(
        protected Queue $queue,
        protected Availability $availability,
    ) {}

    public function handle(Appointment $appointment, AppointmentStatus $to, User $actor, ?string $reason = null): Appointment
    {
        if (! $appointment->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('Cannot change the appointment from “:from” to “:to”.', [
                'from' => $appointment->status->label(),
                'to' => $to->label(),
            ])]);
        }

        return DB::transaction(function () use ($appointment, $to, $actor, $reason) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $attributes = ['status' => $to];

            match ($to) {
                AppointmentStatus::Confirmed => $attributes['confirmed_at'] = now(),
                AppointmentStatus::Arrived => $attributes += $this->arrivedAttributes($appointment),
                AppointmentStatus::InProgress => $attributes['started_at'] = now(),
                AppointmentStatus::Completed => $attributes['completed_at'] = now(),
                AppointmentStatus::Cancelled => $attributes += ['cancelled_at' => now(), 'cancel_reason' => $reason],
                AppointmentStatus::Booked => $this->guardReinstate($appointment),
                AppointmentStatus::NoShow => null,
            };

            $appointment->update($attributes);

            activity('appointments')->causedBy($actor)->performedOn($appointment)
                ->event('status_changed')
                ->withProperties(['to' => $to->value, 'reason' => $reason])
                ->log('appointment status changed');

            return $appointment;
        });
    }

    /** Check-in: give the patient a queue number for the day (kept when coming back from "with doctor"). */
    protected function arrivedAttributes(Appointment $appointment): array
    {
        return [
            'checked_in_at' => $appointment->checked_in_at ?? now(),
            'queue_number' => $appointment->queue_number ?? $this->queue->nextNumber($appointment->doctor_id, $appointment->starts_at),
            'started_at' => null,
        ];
    }

    /** A cancelled / no-show appointment can come back only if its slot is still free. */
    protected function guardReinstate(Appointment $appointment): void
    {
        if ($this->availability->conflictsFor($appointment->doctor_id, $appointment->starts_at, $appointment->ends_at, $appointment->id)->isNotEmpty()) {
            throw ValidationException::withMessages(['status' => __('The time slot has been booked by another patient. Please book a new appointment.')]);
        }
    }
}
