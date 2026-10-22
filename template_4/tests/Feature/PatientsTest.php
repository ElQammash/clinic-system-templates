<?php

use App\Enums\Role;
use App\Livewire\Patients\Index;
use App\Livewire\Patients\PatientEditor;
use App\Livewire\Patients\Show;
use App\Models\CustomField;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

function registerPatient(array $overrides = []): Patient
{
    $component = Livewire::test(PatientEditor::class, ['redirectOnCreate' => false])->call('create');

    foreach (array_merge(['form.name' => 'أحمد محمد علي', 'form.phone' => '01012345678', 'form.gender' => 'male', 'form.birth_date' => '1990-05-01'], $overrides) as $key => $value) {
        $component->set($key, $value);
    }

    $component->call('save')->assertHasNoErrors();

    return Patient::latest('id')->firstOrFail();
}

it('registers a patient with an automatic sequential file number', function () {
    actingAsRole(Role::Receptionist);

    $first = registerPatient();
    $second = registerPatient(['form.name' => 'منى السيد حسن', 'form.phone' => '01112345678']);

    expect($first->file_number)->toBe('00001')
        ->and($second->file_number)->toBe('00002')
        ->and($first->search_name)->toBe('احمد محمد علي');
});

it('uses the configured file number prefix', function () {
    settings()->set(['file_number_prefix' => 'P-']);
    actingAsRole(Role::Admin);

    expect(registerPatient()->file_number)->toBe('P-00001');
});

it('stores an estimated birth date when only the age is known', function () {
    actingAsRole(Role::Receptionist);

    $patient = registerPatient(['form.birthMode' => 'age', 'form.age_years' => '٣٥', 'form.birth_date' => null]);

    expect($patient->birth_date_is_estimated)->toBeTrue()
        ->and($patient->ageInYears())->toBeIn([34, 35]);
});

it('validates the patient form', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test(PatientEditor::class)
        ->call('create')
        ->set('form.name', 'أ')
        ->set('form.phone', '0123')
        ->set('form.national_id', '123')
        ->call('save')
        ->assertHasErrors(['form.name', 'form.phone', 'form.national_id', 'form.birth_date']);
});

it('warns about existing patients with the same phone', function () {
    actingAsRole(Role::Receptionist);
    Patient::factory()->create(['phone' => '01012345678', 'name' => 'سارة محمود']);

    Livewire::test(PatientEditor::class)
        ->call('create')
        ->set('form.phone', '01012345678')
        ->assertSee('سارة محمود');
});

it('finds patients by name regardless of Arabic spelling, by phone and by file number', function () {
    $patient = Patient::factory()->create(['name' => 'فاطمة أحمد مصطفى', 'phone' => '01098765432']);
    Patient::factory()->count(5)->create();

    expect(Patient::search('فاطمه احمد')->pluck('id'))->toContain($patient->id)
        ->and(Patient::search('مصطفي')->pluck('id'))->toContain($patient->id)
        ->and(Patient::search('98765')->pluck('id'))->toContain($patient->id)
        ->and(Patient::search('٠١٠٩٨٧٦٥٤٣٢')->pluck('id'))->toContain($patient->id)
        ->and(Patient::search($patient->file_number)->pluck('id'))->toContain($patient->id)
        ->and(Patient::search('اسم غير موجود')->count())->toBe(0);
});

it('searches from the patients list', function () {
    actingAsRole(Role::Receptionist);
    Patient::factory()->create(['name' => 'يوسف طارق النجار']);
    Patient::factory()->create(['name' => 'ملك حسن البنا']);

    Livewire::test(Index::class)
        ->set('search', 'النجار')
        ->assertSee('يوسف طارق النجار')
        ->assertDontSee('ملك حسن البنا');
});

it('hides the medical background from receptionists', function () {
    $patient = Patient::factory()->create(['allergies' => 'بنسلين', 'chronic_diseases' => 'نقرس', 'blood_type' => 'O+']);

    actingAsRole(Role::Receptionist);
    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($patient->name)
        ->assertDontSee('بنسلين')
        ->assertDontSee('نقرس');

    actingAsRole(Role::Doctor);
    $this->get(route('patients.show', $patient))->assertSee('بنسلين')->assertSee('نقرس');
});

it('does not let receptionists overwrite medical background fields', function () {
    $patient = Patient::factory()->create(['allergies' => 'بنسلين']);
    actingAsRole(Role::Receptionist);

    Livewire::test(PatientEditor::class)
        ->call('edit', $patient->id)
        ->set('form.allergies', '')
        ->set('form.address', 'المعادي')
        ->call('save')
        ->assertHasNoErrors();

    expect($patient->fresh())
        ->allergies->toBe('بنسلين')
        ->address->toBe('المعادي');
});

it('restricts doctors to their own patients when records are not shared', function () {
    settings()->set(['doctors_share_records' => false]);
    $doctor = actingAsRole(Role::Doctor);
    $mine = Patient::factory()->create(['created_by' => $doctor->id, 'allergies' => 'سلفا']);
    $other = Patient::factory()->create(['allergies' => 'لاتكس']);

    expect($doctor->can('viewMedical', $mine))->toBeTrue()
        ->and($doctor->can('viewMedical', $other))->toBeFalse();

    $this->get(route('patients.show', $other))->assertOk()->assertDontSee('لاتكس');

    settings()->set(['doctors_share_records' => true]);
    expect($doctor->fresh()->can('viewMedical', $other))->toBeTrue();
});

it('logs who viewed a medical record', function () {
    $patient = Patient::factory()->create();
    $doctor = actingAsRole(Role::Doctor);

    Livewire::test(Show::class, ['patient' => $patient]);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'medical', 'event' => 'viewed', 'subject_id' => $patient->id, 'causer_id' => $doctor->id,
    ]);
});

it('soft deletes and restores patients, but only with permission', function () {
    $patient = Patient::factory()->create();

    actingAsRole(Role::Receptionist);
    Livewire::test(Show::class, ['patient' => $patient])->call('delete')->assertForbidden();

    actingAsRole(Role::Admin);
    Livewire::test(Show::class, ['patient' => $patient])->call('delete')->assertRedirect(route('patients.index'));
    expect($patient->fresh()->trashed())->toBeTrue();

    Livewire::test(Index::class)->set('trashed', true)->call('restore', $patient->id);
    expect($patient->fresh()->trashed())->toBeFalse();
});

it('forbids users without the permission from the patients list', function () {
    actingAsRole(Role::Accountant);
    $this->get(route('patients.index'))->assertOk();

    $user = User::factory()->create(); // no role at all
    $this->actingAs($user)->get(route('patients.index'))->assertForbidden();
});

it('validates and stores custom patient fields', function () {
    CustomField::create(['entity' => 'patient', 'key' => 'referral', 'label_ar' => 'مصدر التحويل', 'type' => 'select', 'options' => ['فيسبوك', 'صديق'], 'is_required' => true]);
    CustomField::create(['entity' => 'patient', 'key' => 'smoker', 'label_ar' => 'مدخن', 'type' => 'checkbox']);
    CustomField::create(['entity' => 'patient', 'key' => 'dental_only', 'label_ar' => 'للأسنان فقط', 'type' => 'text', 'is_required' => true, 'specialty' => 'dental']);
    actingAsRole(Role::Receptionist);

    Livewire::test(PatientEditor::class)
        ->call('create')
        ->set('form.name', 'زياد خالد')
        ->set('form.birth_date', '2000-01-01')
        ->set('form.custom.referral', 'تويتر')
        ->call('save')
        ->assertHasErrors('form.custom.referral')
        ->assertHasNoErrors('form.custom.dental_only'); // not shown for a general clinic

    $patient = registerPatient(['form.custom.referral' => 'صديق', 'form.custom.smoker' => true]);

    expect($patient->custom_data)->toBe(['referral' => 'صديق', 'smoker' => true]);
});

it('never shows medical details to receptionists on any profile tab or reception screen', function () {
    $patient = Patient::factory()->create(['allergies' => 'بنسلين', 'chronic_diseases' => 'نقرس', 'blood_type' => 'AB-']);
    \App\Models\Visit::factory()->create([
        'patient_id' => $patient->id,
        'chief_complaint' => 'شكوى سرية جدا',
        'diagnosis' => 'تشخيص سري جدا',
    ]);

    actingAsRole(Role::Receptionist);

    // Even when a hidden tab is requested directly, the page falls back to a safe tab.
    foreach (['overview', 'timeline', 'visits', 'appointments', 'billing', 'attachments'] as $tab) {
        Livewire::test(Show::class, ['patient' => $patient])
            ->set('tab', $tab)
            ->assertDontSee('بنسلين')
            ->assertDontSee('نقرس')
            ->assertDontSee('AB-')
            ->assertDontSee('شكوى سرية جدا')
            ->assertDontSee('تشخيص سري جدا');
    }

    foreach (['dashboard', 'queue', 'appointments.index'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee('تشخيص سري جدا')->assertDontSee('بنسلين');
    }
});
