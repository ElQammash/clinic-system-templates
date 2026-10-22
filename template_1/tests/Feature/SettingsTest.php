<?php

use App\Enums\Module;
use App\Enums\Role;
use App\Livewire\Settings\ClinicSettings;
use App\Services\Settings;
use App\Support\Branding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('lets the admin update clinic settings', function () {
    actingAsRole(Role::Admin);

    Livewire::test(ClinicSettings::class)
        ->set('form.clinic_name', 'عيادة الأمل')
        ->set('form.phone', '01012345678')
        ->set('form.currency_code', 'sar')
        ->set('form.module_inventory', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(settings('clinic_name'))->toBe('عيادة الأمل')
        ->and(settings('phone'))->toBe('01012345678')
        ->and(settings('currency_code'))->toBe('SAR')
        ->and(settings('module_inventory'))->toBeTrue();
});

it('validates Egyptian phone numbers', function () {
    actingAsRole(Role::Admin);

    Livewire::test(ClinicSettings::class)
        ->set('form.phone', '0123')
        ->call('save')
        ->assertHasErrors('form.phone');
});

it('accepts Arabic-Indic digits in phone numbers and normalises them', function () {
    actingAsRole(Role::Admin);

    Livewire::test(ClinicSettings::class)
        ->set('form.phone', '٠١٠١٢٣٤٥٦٧٨')
        ->call('save')
        ->assertHasNoErrors();

    expect(settings('phone'))->toBe('01012345678');
});

it('stores the primary color override and renders it as CSS variables', function () {
    actingAsRole(Role::Admin);

    Livewire::test(ClinicSettings::class)->set('form.primary_color', '#2563EB')->call('save')->assertHasNoErrors();

    expect(settings('primary_color'))->toBe('#2563eb')
        ->and((string) Branding::styleTag())->toContain('--color-primary-600: #2563eb;');

    $this->get(route('dashboard'))->assertSee('id="clinic-brand"', false);
});

it('uploads and serves the clinic logo', function () {
    Storage::fake('local');
    actingAsRole(Role::Admin);

    Livewire::test(ClinicSettings::class)
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
        ->call('save')
        ->assertHasNoErrors();

    expect(Branding::hasLogo())->toBeTrue();
    $this->get(route('branding.logo'))->assertOk();
});

it('caches settings and flushes the cache on save', function () {
    settings()->set(['clinic_name' => 'A']);
    expect(Cache::has(Settings::CACHE_KEY))->toBeFalse();

    expect(app(Settings::class)->get('clinic_name'))->toBe('A')
        ->and(Cache::has(Settings::CACHE_KEY))->toBeTrue();

    settings()->set(['clinic_name' => 'B']);
    expect(Cache::has(Settings::CACHE_KEY))->toBeFalse()
        ->and(settings('clinic_name'))->toBe('B');
});

it('logs settings changes in the activity log', function () {
    actingAsRole(Role::Admin);

    Livewire::test(ClinicSettings::class)->set('form.clinic_name', 'عيادة جديدة')->call('save');

    $this->assertDatabaseHas('activity_log', ['log_name' => 'settings', 'event' => 'updated']);
});

it('forbids non-admin roles from the settings pages', function (Role $role) {
    actingAsRole($role);

    $this->get(route('settings.clinic'))->assertForbidden();
    $this->get(route('settings.users'))->assertForbidden();
    $this->get(route('settings.roles'))->assertForbidden();
})->with([Role::Doctor, Role::Receptionist, Role::Accountant]);

it('returns 404 for routes of disabled modules', function () {
    settings()->set(['module_lab' => false]);

    expect(Module::Lab->enabled())->toBeFalse();

    Route::middleware(['web', 'module:lab'])->get('/_module-test', fn () => 'ok');
    actingAsRole(Role::Admin);

    $this->get('/_module-test')->assertNotFound();

    settings()->set(['module_lab' => true]);
    $this->get('/_module-test')->assertOk();
});
