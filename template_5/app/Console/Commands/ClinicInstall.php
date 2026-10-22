<?php

namespace App\Console\Commands;

use App\Actions\Install\InstallClinic;
use App\Enums\ClinicSpecialty;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ClinicInstall extends Command
{
    protected $signature = 'clinic:install
        {--clinic-name= : Clinic name (Arabic)}
        {--clinic-name-en= : Clinic name (English)}
        {--specialty= : Clinic type, e.g. general, dental, pediatrics}
        {--admin-name= : Owner / admin full name}
        {--email= : Admin e-mail (used to log in)}
        {--password= : Admin password (min 8 chars, letters and numbers)}
        {--demo : Also load Arabic demo data (patients, appointments, invoices)}
        {--force : Run without confirmation, even if the clinic is already installed}';

    protected $description = 'Install the clinic: migrate the database, seed base data and create the admin account';

    public function handle(InstallClinic $installer): int
    {
        // Most terminals (notably on Windows) cannot shape Arabic text, so the installer speaks English.
        app()->setLocale('en');

        $this->components->info(__('Clinic installer'));

        if (! $this->ensureAppKey() || ! $this->checkDatabase()) {
            return self::FAILURE;
        }

        if ($this->alreadyInstalled() && ! $this->option('force')
            && ! confirm(__('An admin account already exists. Run the installer again?'), default: false)) {
            $this->components->warn(__('Installation cancelled.'));

            return self::SUCCESS;
        }

        $clinic = [
            'clinic_name' => $this->option('clinic-name') ?: text(
                label: __('Clinic name (Arabic)'),
                placeholder: 'عيادة د. أحمد للأطفال',
                required: true,
            ),
            'clinic_name_en' => $this->option('clinic-name-en'),
            // Only prompt for the clinic type in a fully interactive install (no options given).
            'specialty' => $this->option('specialty') ?: ($this->input->isInteractive() && ! $this->option('clinic-name')
                ? select(label: __('Clinic type'), options: ClinicSpecialty::options(), default: 'general')
                : 'general'),
        ];

        $admin = [
            'name' => $this->option('admin-name') ?: text(label: __('Admin full name'), required: true),
            'email' => $this->option('email') ?: text(label: __('Admin e-mail'), required: true),
            'password' => $this->option('password') ?: password(label: __('Admin password'), required: true),
        ];

        $validator = Validator::make($clinic + $admin, [
            'clinic_name' => ['required', 'string', 'max:150'],
            'specialty' => ['nullable', 'in:'.implode(',', array_column(ClinicSpecialty::cases(), 'value'))],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->components->task(__('Running migrations'), fn () => $this->callSilently('migrate', ['--force' => true]) === 0);

        $this->components->task(__('Seeding base data and creating the admin'), function () use ($installer, $clinic, $admin) {
            $installer->handle($clinic, $admin);
        });

        if ($this->option('demo')) {
            $this->components->task(__('Loading demo data'), fn () => $this->callSilently('db:seed', ['--class' => DemoSeeder::class, '--force' => true]) === 0);
        }

        $this->callSilently('optimize:clear');

        $this->newLine();
        $this->components->info(__('Done! Log in with :email', ['email' => $admin['email']]));

        return self::SUCCESS;
    }

    protected function ensureAppKey(): bool
    {
        if (filled(config('app.key'))) {
            return true;
        }

        if (! file_exists(base_path('.env'))) {
            $this->components->error(__('Missing .env file. Copy .env.example to .env and set the database credentials first.'));

            return false;
        }

        $this->call('key:generate', ['--force' => true]);

        return true;
    }

    protected function checkDatabase(): bool
    {
        try {
            Schema::getConnection()->getPdo();

            return true;
        } catch (Throwable $e) {
            $this->components->error(__('Cannot connect to the database. Check DB_* values in .env.'));
            $this->line('  '.$e->getMessage());

            return false;
        }
    }

    protected function alreadyInstalled(): bool
    {
        try {
            return Schema::hasTable('roles') && User::role(Role::Admin->value)->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
