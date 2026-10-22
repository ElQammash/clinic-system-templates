<?php

namespace Database\Factories;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDay()->setTime(17, 0);

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory()->doctor(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(15),
            'status' => AppointmentStatus::Booked,
            'source' => AppointmentSource::Booked,
        ];
    }

    public function at(string $start, int $minutes = 15): static
    {
        return $this->state(fn () => [
            'starts_at' => $start,
            'ends_at' => now()->parse($start)->addMinutes($minutes),
        ]);
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
