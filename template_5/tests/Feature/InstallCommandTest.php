<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\CustomField;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\User;
use App\Models\VisitType;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;

it('installs the clinic non-interactively', function () {
    $this->artisan('clinic:install', [
        '--clinic-name' => 'عيادة الشفاء',
        '--clinic-name-en' => 'Al Shifa Clinic',
        '--specialty' => 'dental',
        '--admin-name' => 'د. أحمد',
        '--email' => 'Owner@Clinic.test',
        '--password' => 'secret123',
        '--force' => true,
    ])->assertSuccessful();

    $admin = User::where('email', 'owner@clinic.test')->firstOrFail();

    expect($admin->hasRole(Role::Admin->value))->toBeTrue()
        ->and(Hash::check('secret123', $admin->password))->toBeTrue()
        ->and(settings('clinic_name'))->toBe('عيادة الشفاء')
        ->and(settings('clinic_name_en'))->toBe('Al Shifa Clinic')
        ->and(settings('specialty'))->toBe('dental');

    foreach (Role::cases() as $role) {
        expect(RoleModel::where('name', $role->value)->exists())->toBeTrue();
    }
});

it('uses the main clinic name on English screens when no English name is given', function () {
    $this->artisan('clinic:install', [
        '--clinic-name' => 'عيادة النور',
        '--admin-name' => 'د. سارة',
        '--email' => 'owner@clinic.test',
        '--password' => 'secret123',
        '--force' => true,
    ])->assertSuccessful();

    expect(settings()->localized('clinic_name', 'en'))->toBe('عيادة النور');
});

it('asks for the clinic and admin details interactively', function () {
    $this->artisan('clinic:install')
        ->expectsQuestion('Clinic name (Arabic)', 'عيادة النور')
        ->expectsQuestion('Clinic type', 'pediatrics')
        ->expectsQuestion('Admin full name', 'منى')
        ->expectsQuestion('Admin e-mail', 'mona@clinic.test')
        ->expectsQuestion('Admin password', 'secret123')
        ->assertSuccessful();

    expect(User::where('email', 'mona@clinic.test')->first()?->isAdmin())->toBeTrue()
        ->and(settings('specialty'))->toBe('pediatrics');
});

it('adds suggested visit fields and the starter libraries for the clinic type', function () {
    $this->artisan('clinic:install', [
        '--clinic-name' => 'عيادة الابتسامة', '--specialty' => 'dental', '--admin-name' => 'Admin',
        '--email' => 'a@clinic.test', '--password' => 'secret123', '--force' => true,
    ])->assertSuccessful();

    expect(CustomField::where('entity', 'visit')->where('key', 'teeth')->exists())->toBeTrue()
        ->and(Medicine::count())->toBeGreaterThan(30)
        ->and(LabTest::count())->toBeGreaterThan(20)
        ->and(VisitType::count())->toBe(2);
});

it('rejects a weak admin password', function () {
    $this->artisan('clinic:install', [
        '--clinic-name' => 'عيادة', '--admin-name' => 'Admin', '--email' => 'a@clinic.test', '--password' => '123', '--force' => true,
    ])->assertFailed();

    expect(User::count())->toBe(0);
});

it('asks for confirmation when the clinic is already installed', function () {
    User::factory()->admin()->create();

    $this->artisan('clinic:install')
        ->expectsConfirmation('An admin account already exists. Run the installer again?', 'no')
        ->assertSuccessful();

    expect(User::count())->toBe(1);
});

it('is idempotent and keeps role customisations when re-run', function () {
    $args = ['--clinic-name' => 'عيادة', '--admin-name' => 'Admin', '--email' => 'a@clinic.test', '--password' => 'secret123', '--force' => true];
    $this->artisan('clinic:install', $args)->assertSuccessful();

    RoleModel::findByName(Role::Receptionist->value)->givePermissionTo(Permission::ExpensesView->value);

    $this->artisan('clinic:install', $args)->assertSuccessful();

    expect(User::count())->toBe(1)
        ->and(RoleModel::findByName(Role::Receptionist->value)->hasPermissionTo(Permission::ExpensesView->value))->toBeTrue();
});
