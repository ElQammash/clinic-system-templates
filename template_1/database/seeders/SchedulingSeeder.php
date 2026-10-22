<?php

namespace Database\Seeders;

use App\Models\VisitType;
use App\Models\WorkingHour;
use Illuminate\Database\Seeder;

/**
 * Base scheduling data for a new clinic. Only fills empty tables,
 * so the clinic's own changes are never overwritten.
 */
class SchedulingSeeder extends Seeder
{
    public function run(): void
    {
        if (VisitType::count() === 0) {
            VisitType::create(['name_ar' => 'كشف', 'name_en' => 'Consultation', 'default_price' => 300, 'default_duration' => null, 'color' => '#0d9488', 'sort' => 1]);
            VisitType::create(['name_ar' => 'استشارة', 'name_en' => 'Follow-up visit', 'default_price' => 150, 'default_duration' => 10, 'color' => '#2563eb', 'sort' => 2]);
        }

        // Clinic default: Saturday–Thursday 16:00–22:00, Friday off (typical Egyptian evening clinic).
        if (WorkingHour::count() === 0) {
            foreach ([6, 0, 1, 2, 3, 4] as $day) {
                WorkingHour::create(['doctor_id' => null, 'day_of_week' => $day, 'starts_at' => '16:00', 'ends_at' => '22:00']);
            }
        }
    }
}
