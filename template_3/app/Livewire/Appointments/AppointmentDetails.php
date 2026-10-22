<?php

namespace App\Livewire\Appointments;

use App\Actions\Appointments\ChangeAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Visit;
use App\Services\Messaging\AppointmentReminders;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Appointment details + status actions modal. Open with $dispatch('show-appointment', { id }).
 */
class AppointmentDetails extends Component
{
    public bool $open = false;

    public ?int $appointmentId = null;

    public bool $cancelling = false;

    public string $cancelReason = '';

    #[On('show-appointment')]
    public function show(int $id): void
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('view', $appointment);

        $this->appointmentId = $id;
        $this->cancelling = false;
        $this->cancelReason = '';
        $this->resetValidation();
        unset($this->appointment);
        $this->open = true;
    }

    #[Computed]
    public function appointment(): ?Appointment
    {
        return $this->appointmentId
            ? Appointment::with(['patient', 'doctor.doctorProfile', 'visitType', 'creator'])->find($this->appointmentId)
            : null;
    }

    public function changeStatus(string $status, ChangeAppointmentStatus $action): void
    {
        $appointment = $this->appointment;
        abort_if($appointment === null, 404);
        $this->authorize('changeStatus', $appointment);

        $to = AppointmentStatus::from($status);

        if ($to === AppointmentStatus::Cancelled) {
            $this->validate(['cancelReason' => ['nullable', 'string', 'max:255']]);
        }

        $action->handle($appointment, $to, auth()->user(), $to === AppointmentStatus::Cancelled ? ($this->cancelReason ?: null) : null);

        unset($this->appointment);
        $this->cancelling = false;
        $this->dispatch('appointment-updated', id: $appointment->id);
        $this->dispatch('toast', message: __('Appointment updated: :status', ['status' => $to->label()]));
    }

    public function sendReminder(AppointmentReminders $reminders): void
    {
        $this->authorize('notifications.send');
        $appointment = $this->appointment;
        abort_if($appointment === null, 404);
        $this->authorize('view', $appointment);

        $result = $reminders->send($appointment, auth()->user());
        if ($result->url) {
            $this->dispatch('open-url', url: $result->url);
        }
        unset($this->appointment);
    }

    public function reschedule(): void
    {
        $this->open = false;
        $this->dispatch('reschedule-appointment', id: $this->appointmentId);
    }

    /** Link to the appointment's invoice, or to create one (billing staff). */
    protected function invoiceUrl(): ?string
    {
        $appointment = $this->appointment;
        $user = auth()->user();

        if (! $appointment) {
            return null;
        }

        $invoice = Invoice::query()->notVoid()->where('appointment_id', $appointment->id)->first();

        if ($invoice) {
            return $user->can('view', $invoice) ? route('invoices.show', $invoice) : null;
        }

        return $user->can('create', Invoice::class) && in_array($appointment->status, [AppointmentStatus::Arrived, AppointmentStatus::InProgress, AppointmentStatus::Completed], true)
            ? route('invoices.create', ['appointment' => $appointment->id])
            : null;
    }

    public function render()
    {
        $user = auth()->user();
        $appointment = $this->appointment;
        $visit = $appointment ? Visit::query()->where('appointment_id', $appointment->id)->first() : null;

        return view('livewire.appointments.appointment-details', [
            'canChangeStatus' => $appointment && $user->can('changeStatus', $appointment),
            'canUpdate' => $appointment && $user->can('update', $appointment),
            'canStartVisit' => $appointment && $user->can('create', Visit::class)
                && ($appointment->doctor_id === $user->id || $user->can('records.view_all')),
            'visitUrl' => $visit && $user->can('view', $visit) ? route('visits.show', $visit) : null,
            'invoiceUrl' => $this->invoiceUrl(),
        ]);
    }
}
