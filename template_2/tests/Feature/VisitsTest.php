<?php

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\Appointments\BookingForm;
use App\Livewire\Visits\VisitEditor;
use App\Models\Appointment;
use App\Models\CustomField;
use App\Models\Patient;
use App\Models\Visit;
use Database\Seeders\SchedulingSeeder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SchedulingSeeder::class);
    $this->travelTo(Carbon::parse('2026-11-03 17:00')); // Tuesday, clinic open
});

function doctorWithAppointment(AppointmentStatus $status = AppointmentStatus::Arrived): array
{
    $doctor = actingAsRole(Role::Doctor);
    $appointment = Appointment::factory()->for($doctor, 'doctor')->at('2026-11-03 17:00')->status($status)->create();

    return [$doctor, $appointment];
}

it('starts a visit from an appointment and moves the patient into the room', function () {
    [, $appointment] = doctorWithAppointment();

    $response = $this->post(route('visits.start'), ['appointment_id' => $appointment->id]);

    $visit = Visit::firstOrFail();
    $response->assertRedirect(route('visits.show', $visit));
    expect($visit->appointment_id)->toBe($appointment->id)
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::InProgress);

    // Starting again opens the same visit.
    $this->post(route('visits.start'), ['appointment_id' => $appointment->id]);
    expect(Visit::count())->toBe(1);
});

it('checks the patient in automatically when the doctor starts a booked appointment', function () {
    [, $appointment] = doctorWithAppointment(AppointmentStatus::Booked);

    $this->post(route('visits.start'), ['appointment_id' => $appointment->id]);

    expect($appointment->fresh())
        ->status->toBe(AppointmentStatus::InProgress)
        ->queue_number->toBe(1);
});

it('does not let a doctor start a visit for another doctor\'s appointment', function () {
    actingAsRole(Role::Doctor);
    $other = Appointment::factory()->at('2026-11-03 17:00')->status(AppointmentStatus::Arrived)->create();

    $this->post(route('visits.start'), ['appointment_id' => $other->id])->assertForbidden();
});

it('lets a doctor start a visit without an appointment', function () {
    actingAsRole(Role::Doctor);
    $patient = Patient::factory()->create();

    $this->post(route('visits.start'), ['patient_id' => $patient->id])->assertRedirect();

    expect(Visit::where('patient_id', $patient->id)->exists())->toBeTrue();
});

it('keeps receptionists away from medical records', function () {
    $visit = Visit::factory()->create(['examination' => 'سري جدًا']);
    actingAsRole(Role::Receptionist);

    $this->get(route('visits.show', $visit))->assertForbidden();
    $this->get(route('visits.index'))->assertForbidden();
    $this->post(route('visits.start'), ['patient_id' => $visit->patient_id])->assertForbidden();
    $this->get(route('patients.show', $visit->patient))->assertOk()->assertDontSee('سري جدًا');
});

it('keeps accountants away from medical records', function () {
    $visit = Visit::factory()->create();
    actingAsRole(Role::Accountant);

    $this->get(route('visits.show', $visit))->assertForbidden();
});

it('saves vitals and calculates BMI', function () {
    [, $appointment] = doctorWithAppointment();
    $visit = Visit::factory()->create(['doctor_id' => $appointment->doctor_id, 'patient_id' => $appointment->patient_id]);

    Livewire::test(VisitEditor::class, ['visit' => $visit])
        ->set('form.bp_systolic', '١٢٠')
        ->set('form.bp_diastolic', '80')
        ->set('form.weight', '80')
        ->set('form.height', '180')
        ->set('form.diagnosis', 'ارتفاع ضغط')
        ->call('save')
        ->assertHasNoErrors();

    expect($visit->fresh())
        ->bp_systolic->toBe(120)
        ->bmi->toBe(24.7)
        ->diagnosis->toBe('ارتفاع ضغط');
});

it('validates implausible vitals', function () {
    $doctor = actingAsRole(Role::Doctor);
    $visit = Visit::factory()->create(['doctor_id' => $doctor->id]);

    Livewire::test(VisitEditor::class, ['visit' => $visit])
        ->set('form.bp_systolic', '80')
        ->set('form.bp_diastolic', '120')
        ->set('form.temperature', '60')
        ->call('save')
        ->assertHasErrors(['form.bp_diastolic', 'form.temperature']);
});

it('validates required custom visit fields', function () {
    CustomField::create(['entity' => 'visit', 'key' => 'tooth', 'label_ar' => 'رقم السن', 'type' => 'text', 'is_required' => true]);
    $doctor = actingAsRole(Role::Doctor);
    $visit = Visit::factory()->create(['doctor_id' => $doctor->id]);

    Livewire::test(VisitEditor::class, ['visit' => $visit])->call('save')->assertHasErrors('form.custom.tooth');

    Livewire::test(VisitEditor::class, ['visit' => $visit])->set('form.custom.tooth', '36')->call('save')->assertHasNoErrors();
    expect($visit->fresh()->custom_data)->toBe(['tooth' => '36']);
});

it('completes the appointment when the visit is finished', function () {
    [, $appointment] = doctorWithAppointment(AppointmentStatus::InProgress);
    $visit = Visit::factory()->create(['doctor_id' => $appointment->doctor_id, 'patient_id' => $appointment->patient_id, 'appointment_id' => $appointment->id]);

    Livewire::test(VisitEditor::class, ['visit' => $visit])->call('finish')->assertRedirect();

    expect($visit->fresh()->completed_at)->not->toBeNull()
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);
});

it('lets doctors read but not edit another doctor\'s visit when records are shared', function () {
    $visit = Visit::factory()->create();
    actingAsRole(Role::Doctor);

    $this->get(route('visits.show', $visit))->assertOk();
    Livewire::test(VisitEditor::class, ['visit' => $visit])->set('form.diagnosis', 'x')->call('save')->assertForbidden();
});

it('hides other doctors\' visits when records are not shared', function () {
    settings()->set(['doctors_share_records' => false]);
    $visit = Visit::factory()->create();
    actingAsRole(Role::Doctor);

    $this->get(route('visits.show', $visit))->assertForbidden();
});

it('logs every time a visit is opened', function () {
    $doctor = actingAsRole(Role::Doctor);
    $visit = Visit::factory()->create(['doctor_id' => $doctor->id]);

    $this->get(route('visits.show', $visit))->assertOk();

    $this->assertDatabaseHas('activity_log', ['event' => 'viewed', 'subject_type' => Visit::class, 'subject_id' => $visit->id, 'causer_id' => $doctor->id]);
});

it('books the follow-up appointment from the visit and links it', function () {
    $doctor = actingAsRole(Role::Doctor);
    $visit = Visit::factory()->create(['doctor_id' => $doctor->id, 'follow_up_date' => '2026-11-10']);

    Livewire::test(BookingForm::class)
        ->call('openForBooking', patientId: $visit->patient_id, doctorId: $doctor->id, date: '2026-11-10', source: 'follow_up', followUpFor: $visit->id)
        ->assertSet('date', '2026-11-10')
        ->set('time', '18:00')
        ->call('save')
        ->assertHasNoErrors();

    $appointment = Appointment::latest('id')->firstOrFail();
    expect($visit->fresh()->follow_up_appointment_id)->toBe($appointment->id)
        ->and($appointment->source->value)->toBe('follow_up');
});
