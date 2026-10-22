<?php

use App\Actions\Lab\ManageLabRequest;
use App\Enums\LabRequestStatus;
use App\Enums\Role;
use App\Livewire\Lab\Index;
use App\Livewire\Visits\LabRequests;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\PatientAttachment;
use App\Models\Visit;
use Database\Seeders\MedicalLibrarySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(MedicalLibrarySeeder::class);
    Storage::fake('local');
    $this->doctor = actingAsRole(Role::Doctor);
    $this->visit = Visit::factory()->create(['doctor_id' => $this->doctor->id]);
});

it('requests lab tests from a visit', function () {
    $cbc = LabTest::where('name', 'like', '%CBC%')->firstOrFail();

    Livewire::test(LabRequests::class, ['visit' => $this->visit])
        ->set('selectedTests', [$cbc->id])
        ->set('otherTests', 'Vitamin B12, Zinc')
        ->call('create')
        ->assertHasNoErrors();

    $request = LabRequest::with('items')->firstOrFail();
    expect($request->status)->toBe(LabRequestStatus::Requested)
        ->and($request->items->pluck('name')->all())->toBe([$cbc->name, 'Vitamin B12', 'Zinc']);
});

it('requires at least one test', function () {
    Livewire::test(LabRequests::class, ['visit' => $this->visit])->call('create')->assertHasErrors('tests');
});

it('records results with files in the patient file', function () {
    $request = app(ManageLabRequest::class)->create($this->visit->patient, $this->visit, $this->doctor, [LabTest::first()->id]);
    $item = $request->items->first();

    Livewire::test(Index::class)
        ->call('openResults', $request->id)
        ->set("results.{$item->id}", 'Hb 12.5')
        ->set('files', [UploadedFile::fake()->create('cbc.pdf', 100, 'application/pdf')])
        ->call('saveResults')
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe(LabRequestStatus::Completed)
        ->and($item->fresh()->result)->toBe('Hb 12.5')
        ->and(PatientAttachment::where('lab_request_id', $request->id)->where('category', 'lab')->exists())->toBeTrue();
});

it('hides the lab module when it is switched off', function () {
    settings()->set(['module_lab' => false]);

    $this->get(route('lab.index'))->assertNotFound();
});

it('keeps reception out of lab requests', function () {
    actingAsRole(Role::Receptionist);

    $this->get(route('lab.index'))->assertForbidden();
});
