<?php

use App\Actions\Appointments\BookAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\Appointments\BookingForm;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Patient;
use App\Models\User;
use App\Models\WorkingHour;
use App\Services\Scheduling\Availability;
use Database\Seeders\SchedulingSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SchedulingSeeder::class); // clinic hours: Sat–Thu 16:00–22:00
    // Monday morning, so "tomorrow" is a Tuesday working day.
    $this->travelTo(Carbon::parse('2026-11-02 09:00'));

    $this->receptionist = actingAsRole(Role::Receptionist);
    $this->doctor = User::factory()->doctor()->create();
    $this->patient = Patient::factory()->create();
});

function book(array $overrides = []): Appointment
{
    return app(BookAppointment::class)->handle(array_merge([
        'patient_id' => test()->patient->id,
        'doctor_id' => test()->doctor->id,
        'starts_at' => '2026-11-03 17:00',
    ], $overrides), auth()->user());
}

it('books an appointment inside working hours', function () {
    $appointment = book();

    expect($appointment->status)->toBe(AppointmentStatus::Booked)
        ->and($appointment->starts_at->format('H:i'))->toBe('17:00')
        ->and($appointment->ends_at->format('H:i'))->toBe('17:15'); // doctor's 15-minute slot
});

it('prevents double booking the same doctor', function () {
    book();

    expect(fn () => book(['patient_id' => Patient::factory()->create()->id]))
        ->toThrow(ValidationException::class);

    expect(fn () => book(['patient_id' => Patient::factory()->create()->id, 'starts_at' => '2026-11-03 17:10']))
        ->toThrow(ValidationException::class);
});

it('allows back-to-back appointments', function () {
    book();
    $next = book(['patient_id' => Patient::factory()->create()->id, 'starts_at' => '2026-11-03 17:15']);

    expect($next->exists)->toBeTrue();
});

it('allows booking another doctor at the same time', function () {
    book();
    $other = book(['patient_id' => Patient::factory()->create()->id, 'doctor_id' => User::factory()->doctor()->create()->id]);

    expect($other->exists)->toBeTrue();
});

it('frees the slot when an appointment is cancelled or a no-show', function (AppointmentStatus $status) {
    book()->update(['status' => $status]);

    $again = book(['patient_id' => Patient::factory()->create()->id]);

    expect($again->exists)->toBeTrue();
})->with([AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);

it('rejects times outside working hours', function () {
    expect(fn () => book(['starts_at' => '2026-11-03 10:00']))->toThrow(ValidationException::class);
    expect(fn () => book(['starts_at' => '2026-11-03 21:50']))->toThrow(ValidationException::class); // would end at 22:05
    expect(fn () => book(['starts_at' => '2026-11-06 17:00']))->toThrow(ValidationException::class); // Friday off
});

it('lets schedule managers book outside working hours explicitly', function () {
    actingAsRole(Role::Admin);

    expect(book(['starts_at' => '2026-11-03 10:00', 'outside_hours' => true])->exists)->toBeTrue();
});

it('ignores the outside-hours flag for users who cannot manage schedules', function () {
    expect(fn () => book(['starts_at' => '2026-11-03 10:00', 'outside_hours' => true]))->toThrow(ValidationException::class);
});

it('respects doctor-specific working hours', function () {
    WorkingHour::create(['doctor_id' => $this->doctor->id, 'day_of_week' => 2, 'starts_at' => '10:00', 'ends_at' => '13:00']);

    expect(book(['starts_at' => '2026-11-03 10:00'])->exists)->toBeTrue();
    expect(fn () => book(['starts_at' => '2026-11-03 17:00', 'patient_id' => Patient::factory()->create()->id]))->toThrow(ValidationException::class);
});

it('rejects blocked days for the doctor or the whole clinic', function () {
    BlockedPeriod::create(['doctor_id' => $this->doctor->id, 'starts_at' => '2026-11-03 00:00', 'ends_at' => '2026-11-03 23:59', 'reason' => 'مؤتمر']);
    expect(fn () => book())->toThrow(ValidationException::class);

    BlockedPeriod::create(['doctor_id' => null, 'starts_at' => '2026-11-04 00:00', 'ends_at' => '2026-11-04 23:59', 'reason' => 'إجازة رسمية']);
    expect(fn () => book(['starts_at' => '2026-11-04 17:00']))->toThrow(ValidationException::class);
});

it('rejects a patient being booked twice at the same time with different doctors', function () {
    book();

    expect(fn () => book(['doctor_id' => User::factory()->doctor()->create()->id]))->toThrow(ValidationException::class);
});

it('rejects times in the past', function () {
    $this->travelTo(Carbon::parse('2026-11-03 18:00'));

    expect(fn () => book(['starts_at' => '2026-11-03 17:00']))->toThrow(ValidationException::class);
});

it('reschedules without conflicting with itself', function () {
    $appointment = book();

    app(BookAppointment::class)->handle([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'starts_at' => '2026-11-03 17:05',
    ], auth()->user(), $appointment);

    expect($appointment->fresh()->starts_at->format('H:i'))->toBe('17:05')
        ->and(Appointment::count())->toBe(1);
});

it('lists free slots excluding booked ones', function () {
    book();

    $slots = collect(app(Availability::class)->freeSlots($this->doctor, Carbon::parse('2026-11-03'), 15))
        ->map->format('H:i');

    expect($slots)->not->toContain('17:00')
        ->and($slots)->toContain('16:00', '17:15', '21:45')
        ->and($slots)->not->toContain('22:00');
});

it('books through the booking form', function () {
    Livewire::test(BookingForm::class)
        ->call('openForBooking', patientId: $this->patient->id, doctorId: $this->doctor->id)
        ->set('date', '2026-11-03')
        ->set('time', '18:30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('appointment-saved');

    expect(Appointment::first()->starts_at->format('Y-m-d H:i'))->toBe('2026-11-03 18:30');
});

it('shows conflict errors in the booking form', function () {
    book();

    Livewire::test(BookingForm::class)
        ->call('openForBooking', patientId: Patient::factory()->create()->id, doctorId: $this->doctor->id)
        ->set('date', '2026-11-03')
        ->set('time', '17:00')
        ->call('save')
        ->assertHasErrors('starts_at');
});

it('does not let a doctor book into another doctor\'s schedule', function () {
    actingAsRole(Role::Doctor);

    Livewire::test(BookingForm::class)
        ->call('openForBooking', patientId: $this->patient->id)
        ->set('doctorId', $this->doctor->id)
        ->set('date', '2026-11-03')
        ->set('time', '17:00')
        ->call('save')
        ->assertForbidden();
});
