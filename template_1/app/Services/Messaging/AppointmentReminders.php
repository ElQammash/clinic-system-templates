<?php

namespace App\Services\Messaging;

use App\Contracts\MessageChannel;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\User;
use App\Support\Branding;
use App\Support\Format;
use App\Support\Messaging\MessageResult;
use App\Support\Messaging\OutgoingMessage;
use Illuminate\Validation\ValidationException;

/**
 * Builds and sends appointment reminders through the configured MessageChannel,
 * logging every attempt in notification_logs.
 */
class AppointmentReminders
{
    public const PLACEHOLDERS = ['{patient}', '{doctor}', '{date}', '{day}', '{time}', '{clinic}', '{phone}', '{address}'];

    public function __construct(protected MessageChannel $channel) {}

    public function channel(): MessageChannel
    {
        return $this->channel;
    }

    /** The reminder text with placeholders filled in (always in Arabic unless the clinic changes the template). */
    public function message(Appointment $appointment): string
    {
        $appointment->loadMissing('patient', 'doctor');
        $template = (string) settings('reminder_template', config('clinic.settings.reminder_template'));

        // Reminders use the clinic's default language, not the receptionist's UI language.
        $locale = app()->getLocale();
        app()->setLocale(settings('default_locale', 'ar'));

        try {
            return strtr($template, [
                '{patient}' => $appointment->patient->name,
                '{doctor}' => $appointment->doctor->displayName(),
                '{date}' => Format::date($appointment->starts_at),
                '{day}' => Format::date($appointment->starts_at, 'l'),
                '{time}' => Format::time($appointment->starts_at),
                '{clinic}' => Branding::name(),
                '{phone}' => (string) settings('phone'),
                '{address}' => (string) Branding::address(),
            ]);
        } finally {
            app()->setLocale($locale);
        }
    }

    public function send(Appointment $appointment, User $sender): MessageResult
    {
        $appointment->loadMissing('patient');
        $to = Format::internationalPhone($appointment->patient->phone);

        if (! $to) {
            throw ValidationException::withMessages(['phone' => __('This patient has no phone number.')]);
        }

        $body = $this->message($appointment);
        $result = $this->channel->send(new OutgoingMessage($to, $body));

        NotificationLog::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'channel' => $this->channel->name(),
            'recipient' => $to,
            'message' => $body,
            'status' => $result->status,
            'provider_reference' => $result->reference,
            'error' => $result->error,
            'sent_by' => $sender->id,
        ]);

        if ($result->status !== 'failed') {
            $appointment->forceFill(['reminder_sent_at' => now()])->save();
        }

        return $result;
    }
}
