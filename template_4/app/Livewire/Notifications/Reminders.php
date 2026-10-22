<?php

namespace App\Livewire\Notifications;

use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Models\Appointment;
use App\Services\Messaging\AppointmentReminders;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tomorrow's (or any day's) appointments with one-click WhatsApp reminders.
 */
#[Title('Reminders')]
class Reminders extends Component
{
    #[Url]
    public string $date = '';

    public function mount(): void
    {
        $this->authorize(Permission::NotificationsSend->value);
        $this->date = $this->date ?: today()->addDay()->toDateString();
    }

    public function send(int $id, AppointmentReminders $reminders): void
    {
        $this->authorize(Permission::NotificationsSend->value);

        $appointment = Appointment::query()->visibleTo(auth()->user())->findOrFail($id);
        $result = $reminders->send($appointment, auth()->user());

        if ($result->url) {
            $this->dispatch('open-url', url: $result->url);
        }

        $this->dispatch('toast', message: $result->status === 'failed' ? __('Sending failed: :error', ['error' => $result->error]) : __('Reminder prepared for :name.', ['name' => $appointment->patient->name]), type: $result->status === 'failed' ? 'error' : 'success');
    }

    public function render(AppointmentReminders $reminders)
    {
        try {
            $day = CarbonImmutable::parse($this->date);
        } catch (\Throwable) {
            $day = CarbonImmutable::tomorrow();
        }

        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->visibleTo(auth()->user())
            ->onDate($day)
            ->whereIn('status', [AppointmentStatus::Booked->value, AppointmentStatus::Confirmed->value])
            ->orderBy('starts_at')
            ->get();

        return view('livewire.notifications.reminders', [
            'appointments' => $appointments,
            'preview' => $appointments->first() ? $reminders->message($appointments->first()) : null,
            'automatic' => $reminders->channel()->sendsAutomatically(),
            'day' => $day,
        ]);
    }
}
