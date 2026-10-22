<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory()->doctor(),
            'visited_at' => now(),
            'chief_complaint' => 'سخونية وكحة',
            'diagnosis' => 'التهاب حلق',
        ];
    }
}
