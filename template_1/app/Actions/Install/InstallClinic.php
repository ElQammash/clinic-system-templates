<?php

namespace App\Actions\Install;

use App\Enums\ClinicSpecialty;
use App\Enums\Role;
use App\Models\User;
use App\Support\CustomFieldPresets;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Seeds base data, stores the clinic identity and creates (or updates) the
 * owner account. Migrations are run by the clinic:install command beforehand.
 */
class InstallClinic
{
    /**
     * @param  array{clinic_name: string, clinic_name_en?: ?string, specialty?: ?string, phone?: ?string, default_locale?: ?string}  $clinic
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public function handle(array $clinic, array $admin): User
    {
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        return DB::transaction(function () use ($clinic, $admin) {
            settings()->set(array_filter([
                'clinic_name' => $clinic['clinic_name'],
                // Without an English name, English screens show the main name rather than the generic default.
                'clinic_name_en' => $clinic['clinic_name_en'] ?? $clinic['clinic_name'],
                'specialty' => ClinicSpecialty::tryFrom($clinic['specialty'] ?? '')?->value,
                'phone' => $clinic['phone'] ?? null,
                'default_locale' => $clinic['default_locale'] ?? null,
            ], fn ($value) => filled($value)));

            $user = User::query()->updateOrCreate(
                ['email' => mb_strtolower(trim($admin['email']))],
                [
                    'name' => $admin['name'],
                    'password' => $admin['password'],
                    'is_active' => true,
                ],
            );

            $user->syncRoles([Role::Admin->value]);

            // Suggested visit fields for the clinic type (e.g. teeth for dental clinics).
            if ($specialty = ClinicSpecialty::tryFrom($clinic['specialty'] ?? '')) {
                CustomFieldPresets::apply($specialty);
            }

            activity('system')->causedBy($user)->event('installed')->log('clinic installed');

            return $user;
        });
    }
}
