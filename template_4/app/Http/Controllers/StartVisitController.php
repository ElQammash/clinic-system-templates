<?php

namespace App\Http\Controllers;

use App\Actions\Visits\StartVisit;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * POST /visits/start with appointment_id (from the queue / calendar)
 * or patient_id (a visit without an appointment, from the patient profile).
 */
class StartVisitController extends Controller
{
    public function __invoke(Request $request, StartVisit $action): RedirectResponse
    {
        Gate::authorize('create', Visit::class);

        $data = $request->validate([
            'appointment_id' => ['nullable', 'required_without:patient_id', 'integer', 'exists:appointments,id'],
            'patient_id' => ['nullable', 'required_without:appointment_id', 'integer', 'exists:patients,id'],
        ]);

        $user = $request->user();

        if (filled($data['appointment_id'] ?? null)) {
            $appointment = Appointment::findOrFail($data['appointment_id']);
            Gate::authorize('changeStatus', $appointment);

            // Only the appointment's doctor (or someone with access to all records) opens the visit.
            abort_unless($appointment->doctor_id === $user->id || $user->can('records.view_all'), 403);

            $visit = $action->handle(Patient::findOrFail($appointment->patient_id), $user, $appointment);
        } else {
            $patient = Patient::findOrFail($data['patient_id']);
            abort_if($patient->trashed(), 404);
            abort_unless($user->isDoctor(), 403, __('Only doctors can start a visit without an appointment.'));

            $visit = $action->handle($patient, $user);
        }

        return redirect()->route('visits.show', $visit);
    }
}
