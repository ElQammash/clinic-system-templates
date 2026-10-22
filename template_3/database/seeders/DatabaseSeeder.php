<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Base data every clinic needs. Safe to run multiple times.
 * Demo data lives in DemoSeeder (php artisan db:seed --class=DemoSeeder).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SchedulingSeeder::class,
            MedicalLibrarySeeder::class,
            BillingSeeder::class,
        ]);
    }
}
