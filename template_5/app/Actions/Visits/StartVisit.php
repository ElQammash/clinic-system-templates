<?php

namespace App\Actions\Visits;

use App\Actions\Appointments\ChangeAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Opens the visit for an appointment (moving the patient "into the room"),
 * or a stand-alone visit when the doctor sees a patient without an appointment.
 * Calling it twice for the same appointment returns the same visit.
 */
class StartVisit
{
    public function __construct(protected ChangeAppointmentStatus $status) {}

    public function handle(Patient $patient, User $doctor, ?Appointment $appointment = null): Visit
    {
        if ($appointment && ($existing = Visit::query()->where('appointment_id', $appointment->id)->first())) {
            return $existing;
        }

        return DB::transaction(function () use ($patient, $doctor, $appointment) {
            if ($appointment) {
                if (in_array($appointment->status, [AppointmentStatus::Booked, AppointmentStatus::Confirmed, AppointmentStatus::NoShow], true)) {
                    $appointment = $this->status->handle($appointment, AppointmentStatus::Arrived, $doctor);
                }
                if ($appointment->status === AppointmentStatus::Arrived) {
                    $this->status->handle($appointment, AppointmentStatus::InProgress, $doctor);
                }
            }

            $visit = Visit::create([
                'patient_id' => $patient->id,
                'doctor_id' => $appointment?->doctor_id ?? $doctor->id,
                'appointment_id' => $appointment?->id,
                'visited_at' => now(),
            ]);

            activity('medical')->causedBy($doctor)->performedOn($visit)->event('created')->log('visit started');

            return $visit;
        });
    }
}
