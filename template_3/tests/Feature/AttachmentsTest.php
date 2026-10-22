<?php

use App\Enums\Role;
use App\Livewire\Patients\AttachmentManager;
use App\Models\Patient;
use App\Models\PatientAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->patient = Patient::factory()->create();
});

function uploadXray(Patient $patient): PatientAttachment
{
    Livewire::test(AttachmentManager::class, ['patient' => $patient])
        ->set('files', [UploadedFile::fake()->image('chest.jpg', 800, 600)])
        ->set('category', 'xray')
        ->set('title', 'أشعة صدر')
        ->call('upload')
        ->assertHasNoErrors();

    return PatientAttachment::latest('id')->firstOrFail();
}

it('lets receptionists upload files but not open them', function () {
    actingAsRole(Role::Receptionist);

    $attachment = uploadXray($this->patient);

    expect($attachment->title)->toBe('أشعة صدر')
        ->and($attachment->path)->toStartWith("patients/{$this->patient->id}/");
    Storage::disk('local')->assertExists($attachment->path);

    $this->get(route('attachments.show', $attachment))->assertForbidden();
});

it('lets doctors open files and records each access', function () {
    actingAsRole(Role::Receptionist);
    $attachment = uploadXray($this->patient);

    $doctor = actingAsRole(Role::Doctor);
    $this->get(route('attachments.show', $attachment))->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    $this->assertDatabaseHas('activity_log', ['event' => 'attachment_viewed', 'causer_id' => $doctor->id]);
});

it('stores files privately, never under the public disk', function () {
    actingAsRole(Role::Doctor);
    $attachment = uploadXray($this->patient);

    expect($attachment->disk)->toBe('local');
    $this->assertFileDoesNotExist(public_path('storage/'.$attachment->path));
});

it('rejects dangerous file types', function () {
    actingAsRole(Role::Doctor);

    Livewire::test(AttachmentManager::class, ['patient' => $this->patient])
        ->set('files', [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
        ->call('upload')
        ->assertHasErrors('files.0');

    expect(PatientAttachment::count())->toBe(0);
});

it('uploads several files at once', function () {
    actingAsRole(Role::Doctor);

    Livewire::test(AttachmentManager::class, ['patient' => $this->patient])
        ->set('files', [UploadedFile::fake()->create('cbc.pdf', 200, 'application/pdf'), UploadedFile::fake()->image('rash.png')])
        ->set('category', 'lab')
        ->call('upload')
        ->assertHasNoErrors();

    expect($this->patient->attachments()->pluck('title')->sort()->values()->all())->toBe(['cbc', 'rash']);
});

it('deletes files only with permission and removes them from storage', function () {
    actingAsRole(Role::Doctor);
    $attachment = uploadXray($this->patient);

    Livewire::test(AttachmentManager::class, ['patient' => $this->patient])->call('delete', $attachment->id)->assertForbidden();

    actingAsRole(Role::Admin);
    Livewire::test(AttachmentManager::class, ['patient' => $this->patient])->call('delete', $attachment->id);

    expect(PatientAttachment::count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->path);
});
