<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Settings\Roles;
use App\Livewire\Settings\Users;
use App\Models\User;
use Livewire\Livewire;

it('creates a doctor with a doctor profile', function () {
    actingAsRole(Role::Admin);

    Livewire::test(Users::class)
        ->call('create')
        ->set('form.name', 'د. منى السيد')
        ->set('form.email', 'mona@clinic.test')
        ->set('form.phone', '01112345678')
        ->set('form.password', 'secret123')
        ->set('form.role', Role::Doctor->value)
        ->set('form.title_ar', 'أخصائي الجلدية')
        ->set('form.slot_minutes', 20)
        ->set('form.consultation_fee', 350.5)
        ->call('save')
        ->assertHasNoErrors();

    $doctor = User::where('email', 'mona@clinic.test')->firstOrFail();

    expect($doctor->isDoctor())->toBeTrue()
        ->and($doctor->doctorProfile->slot_minutes)->toBe(20)
        ->and($doctor->doctorProfile->consultation_fee)->toBe(350.5)
        ->and($doctor->doctorProfile->getRawOriginal('consultation_fee'))->toBe(35050);
});

it('validates unique e-mails and required passwords for new users', function () {
    actingAsRole(Role::Admin);
    User::factory()->create(['email' => 'taken@clinic.test']);

    Livewire::test(Users::class)
        ->call('create')
        ->set('form.name', 'X')
        ->set('form.email', 'taken@clinic.test')
        ->call('save')
        ->assertHasErrors(['form.email', 'form.password']);
});

it('prevents the admin from deactivating themselves', function () {
    $admin = actingAsRole(Role::Admin);

    Livewire::test(Users::class)->call('toggleActive', $admin->id)->assertHasErrors('is_active');

    expect($admin->fresh()->is_active)->toBeTrue();
});

it('keeps at least one active admin', function () {
    $me = actingAsRole(Role::Admin);
    $other = User::factory()->admin()->create();

    // Demoting the other admin is fine while I remain admin.
    Livewire::test(Users::class)->call('edit', $other->id)->set('form.role', Role::Doctor->value)->call('save')->assertHasNoErrors();
    expect($other->fresh()->isAdmin())->toBeFalse();

    // Removing my own admin role is refused.
    Livewire::test(Users::class)->call('edit', $me->id)->set('form.role', Role::Receptionist->value)->call('save')->assertHasErrors('role');
});

it('deactivates other users', function () {
    actingAsRole(Role::Admin);
    $receptionist = User::factory()->receptionist()->create();

    Livewire::test(Users::class)->call('toggleActive', $receptionist->id)->assertHasNoErrors();

    expect($receptionist->fresh()->is_active)->toBeFalse();
});

it('seeds sensible default permissions per role', function () {
    $receptionist = User::factory()->receptionist()->create();
    $doctor = User::factory()->doctor()->create();
    $accountant = User::factory()->accountant()->create();

    // Receptionists handle patients and payments but never see medical notes.
    expect($receptionist->can(Permission::PatientsCreate->value))->toBeTrue()
        ->and($receptionist->can(Permission::PaymentsCreate->value))->toBeTrue()
        ->and($receptionist->can(Permission::VisitsView->value))->toBeFalse()
        ->and($receptionist->can(Permission::AttachmentsView->value))->toBeFalse()
        ->and($receptionist->can(Permission::PrescriptionsManage->value))->toBeFalse();

    expect($doctor->can(Permission::VisitsView->value))->toBeTrue()
        ->and($doctor->can(Permission::SettingsManage->value))->toBeFalse();

    expect($accountant->can(Permission::ExpensesManage->value))->toBeTrue()
        ->and($accountant->can(Permission::VisitsView->value))->toBeFalse();
});

it('lets the admin customise role permissions but never the admin role', function () {
    $admin = actingAsRole(Role::Admin);

    Livewire::test(Roles::class)
        ->set('matrix.receptionist', [Permission::PatientsView->value, Permission::ExpensesView->value])
        ->call('save')
        ->assertHasNoErrors();

    $receptionist = User::factory()->receptionist()->create();
    expect($receptionist->can(Permission::ExpensesView->value))->toBeTrue()
        ->and($receptionist->can(Permission::PaymentsCreate->value))->toBeFalse()
        ->and($admin->can(Permission::BackupsManage->value))->toBeTrue();
});
