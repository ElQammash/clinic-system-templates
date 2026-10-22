<?php

use App\Actions\Appointments\ChangeAppointmentStatus;
use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\Appointments\Calendar;
use App\Livewire\Queue\WaitingRoom;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\SchedulingSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SchedulingSeeder::class);
    $this->travelTo(Carbon::parse('2026-11-03 16:30')); // Tuesday, clinic open
    $this->doctor = User::factory()->doctor()->create();
});

function todayAppointment(string $time, ?User $doctor = null, AppointmentStatus $status = AppointmentStatus::Booked): Appointment
{
    return Appointment::factory()
        ->for($doctor ?? test()->doctor, 'doctor')
        ->at("2026-11-03 {$time}")
        ->status($status)
        ->create();
}

it('gives queue numbers in check-in order, per doctor and per day', function () {
    $user = actingAsRole(Role::Receptionist);
    $action = app(ChangeAppointmentStatus::class);

    $late = todayAppointment('17:00');
    $early = todayAppointment('16:45');
    $otherDoctor = todayAppointment('16:45', User::factory()->doctor()->create());

    expect($action->handle($late, AppointmentStatus::Arrived, $user)->queue_number)->toBe(1)
        ->and($action->handle($early, AppointmentStatus::Arrived, $user)->queue_number)->toBe(2)
        ->and($action->handle($otherDoctor, AppointmentStatus::Arrived, $user)->queue_number)->toBe(1);
});

it('stamps times through the appointment life cycle', function () {
    $user = actingAsRole(Role::Receptionist);
    $action = app(ChangeAppointmentStatus::class);
    $a = todayAppointment('17:00');

    $a = $action->handle($a, AppointmentStatus::Confirmed, $user);
    $a = $action->handle($a, AppointmentStatus::Arrived, $user);
    $a = $action->handle($a, AppointmentStatus::InProgress, $user);
    $a = $action->handle($a, AppointmentStatus::Completed, $user);

    expect($a->confirmed_at)->not->toBeNull()
        ->and($a->checked_in_at)->not->toBeNull()
        ->and($a->started_at)->not->toBeNull()
        ->and($a->completed_at)->not->toBeNull();
});

it('rejects invalid status transitions', function () {
    $user = actingAsRole(Role::Receptionist);

    expect(fn () => app(ChangeAppointmentStatus::class)->handle(todayAppointment('17:00'), AppointmentStatus::Completed, $user))
        ->toThrow(ValidationException::class);
});

it('stores the cancellation reason', function () {
    $user = actingAsRole(Role::Receptionist);

    $a = app(ChangeAppointmentStatus::class)->handle(todayAppointment('17:00'), AppointmentStatus::Cancelled, $user, 'ظروف سفر');

    expect($a->cancel_reason)->toBe('ظروف سفر')->and($a->cancelled_at)->not->toBeNull();
});

it('does not reinstate a cancelled appointment whose slot was taken', function () {
    $user = actingAsRole(Role::Receptionist);
    $cancelled = todayAppointment('17:00', status: AppointmentStatus::Cancelled);
    todayAppointment('17:00');

    expect(fn () => app(ChangeAppointmentStatus::class)->handle($cancelled, AppointmentStatus::Booked, $user))
        ->toThrow(ValidationException::class);
});

it('registers walk-ins straight into the queue', function () {
    actingAsRole(Role::Receptionist);
    todayAppointment('16:15', status: AppointmentStatus::Arrived)->update(['queue_number' => 1]);
    $patient = Patient::factory()->create();

    Livewire::test(WaitingRoom::class)
        ->call('openWalkIn')
        ->set('walkInPatientId', $patient->id)
        ->set('walkInDoctorId', $this->doctor->id)
        ->call('registerWalkIn')
        ->assertHasNoErrors();

    $walkIn = Appointment::where('patient_id', $patient->id)->firstOrFail();

    expect($walkIn->source)->toBe(AppointmentSource::WalkIn)
        ->and($walkIn->status)->toBe(AppointmentStatus::Arrived)
        ->and($walkIn->queue_number)->toBe(2);
});

it('calls the next waiting patient by queue number', function () {
    actingAsRole(Role::Receptionist);
    $second = todayAppointment('16:00', status: AppointmentStatus::Arrived);
    $second->update(['queue_number' => 2, 'checked_in_at' => now()]);
    $first = todayAppointment('16:15', status: AppointmentStatus::Arrived);
    $first->update(['queue_number' => 1, 'checked_in_at' => now()]);

    Livewire::test(WaitingRoom::class)->call('callNext', $this->doctor->id);

    expect($first->fresh()->status)->toBe(AppointmentStatus::InProgress)
        ->and($second->fresh()->status)->toBe(AppointmentStatus::Arrived);
});

it('shows doctors only their own waiting room and calendar', function () {
    $me = actingAsRole(Role::Doctor);
    $mine = todayAppointment('17:00', $me);
    $other = todayAppointment('17:00');

    Livewire::test(WaitingRoom::class)
        ->assertSee($mine->patient->name)
        ->assertDontSee($other->patient->name);

    Livewire::test(Calendar::class, ['date' => '2026-11-03'])
        ->assertSet('doctorId', $me->id)
        ->assertSee($mine->patient->name)
        ->assertDontSee($other->patient->name);
});

it('lets reception see every doctor in the calendar', function () {
    actingAsRole(Role::Receptionist);
    $a = todayAppointment('17:00');
    $b = todayAppointment('17:00', User::factory()->doctor()->create());

    Livewire::test(Calendar::class, ['date' => '2026-11-03'])
        ->assertSee($a->patient->name)
        ->assertSee($b->patient->name);
});

it('forbids the accountant from the appointments screens', function () {
    actingAsRole(Role::Accountant);

    $this->get(route('appointments.index'))->assertForbidden();
    $this->get(route('queue'))->assertForbidden();
});

it('treats a patient with an appointment as the doctor\'s patient when records are not shared', function () {
    settings()->set(['doctors_share_records' => false]);
    $doctor = actingAsRole(Role::Doctor);
    $patient = Patient::factory()->create();

    expect($doctor->can('viewMedical', $patient))->toBeFalse();

    todayAppointment('17:00', $doctor)->update(['patient_id' => $patient->id]);

    expect($doctor->can('viewMedical', $patient))->toBeTrue();
});

it('renders the calendar, queue and schedule pages', function () {
    actingAsRole(Role::Admin);
    todayAppointment('17:00');

    $this->get(route('appointments.index'))->assertOk();
    $this->get(route('appointments.index', ['view' => 'week', 'doctor' => $this->doctor->id]))->assertOk();
    $this->get(route('queue'))->assertOk();
    $this->get(route('settings.schedule'))->assertOk();
    $this->get(route('settings.schedule', ['tab' => 'types']))->assertOk();
    $this->get(route('settings.schedule', ['tab' => 'off']))->assertOk();
});
