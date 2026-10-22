<?php

use App\Enums\Role;
use App\Livewire\Visits\PrescriptionBuilder;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionTemplate;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\MedicalLibrarySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(MedicalLibrarySeeder::class);
    $this->doctor = actingAsRole(Role::Doctor);
    $this->visit = Visit::factory()->create(['doctor_id' => $this->doctor->id, 'weight' => 20]);
});

it('builds a prescription from the medicines library', function () {
    $augmentin = Medicine::where('name', 'Augmentin')->firstOrFail();

    Livewire::test(PrescriptionBuilder::class, ['visit' => $this->visit])
        ->set('medicineSearch', 'augm')
        ->assertSee('Augmentin')
        ->call('addMedicine', $augmentin->id)
        ->assertSet('items.0.frequency', 'كل 12 ساعة')
        ->set('notes', 'راحة تامة')
        ->call('save')
        ->assertHasNoErrors();

    $prescription = Prescription::with('items')->firstOrFail();

    expect($prescription->visit_id)->toBe($this->visit->id)
        ->and($prescription->items)->toHaveCount(1)
        ->and($prescription->items[0]->medicine_name)->toBe('Augmentin 1g')
        ->and($augmentin->fresh()->times_prescribed)->toBe(1);
});

it('adds hand-typed medicines to the library', function () {
    Livewire::test(PrescriptionBuilder::class, ['visit' => $this->visit])
        ->set('medicineSearch', 'Mebo Ointment')
        ->call('addMedicine')
        ->set('items.0.dose', 'دهان')
        ->call('save')
        ->assertHasNoErrors();

    expect(Medicine::where('name', 'Mebo Ointment')->exists())->toBeTrue();
});

it('replaces the items when the prescription is saved again', function () {
    $component = Livewire::test(PrescriptionBuilder::class, ['visit' => $this->visit])
        ->set('medicineSearch', 'Panadol')->call('addMedicine')
        ->set('medicineSearch', 'Brufen')->call('addMedicine')
        ->call('save');

    $component->call('removeItem', 0)->call('save');

    expect(Prescription::count())->toBe(1)
        ->and(Prescription::first()->items()->pluck('medicine_name')->all())->toBe(['Brufen']);
});

it('saves and applies prescription templates', function () {
    Livewire::test(PrescriptionBuilder::class, ['visit' => $this->visit])
        ->set('medicineSearch', 'Panadol')->call('addMedicine')
        ->set('medicineSearch', 'Telfast')->call('addMedicine')
        ->set('showSaveTemplate', true)
        ->set('templateName', 'نزلة برد')
        ->call('saveAsTemplate')
        ->assertHasNoErrors();

    $template = PrescriptionTemplate::where('name', 'نزلة برد')->firstOrFail();
    expect($template->doctor_id)->toBe($this->doctor->id)->and($template->items)->toHaveCount(2);

    $otherVisit = Visit::factory()->create(['doctor_id' => $this->doctor->id]);
    Livewire::test(PrescriptionBuilder::class, ['visit' => $otherVisit])
        ->call('applyTemplate', $template->id)
        ->assertCount('items', 2);
});

it('does not let doctors use another doctor\'s private template', function () {
    $template = PrescriptionTemplate::create(['doctor_id' => User::factory()->doctor()->create()->id, 'name' => 'خاص']);

    Livewire::test(PrescriptionBuilder::class, ['visit' => $this->visit])
        ->call('applyTemplate', $template->id)
        ->assertNotFound();
});

it('prints the prescription as an A5 page and as a PDF', function () {
    Livewire::test(PrescriptionBuilder::class, ['visit' => $this->visit])
        ->set('medicineSearch', 'Cetal')->call('addMedicine', Medicine::where('name', 'Cetal Syrup')->value('id'))
        ->call('save');

    $prescription = Prescription::firstOrFail();

    $this->get(route('print.prescription', $prescription))
        ->assertOk()
        ->assertSee('Cetal Syrup')
        ->assertSee($this->visit->patient->name);

    $pdf = $this->get(route('print.prescription', [$prescription, 'format' => 'pdf']));
    $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');
});

it('does not let reception print prescriptions', function () {
    $prescription = Prescription::create(['visit_id' => $this->visit->id, 'patient_id' => $this->visit->patient_id, 'doctor_id' => $this->doctor->id, 'issued_at' => now()]);
    actingAsRole(Role::Receptionist);

    $this->get(route('print.prescription', $prescription))->assertForbidden();
});
