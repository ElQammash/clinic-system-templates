<?php

namespace App\Livewire\Patients;

use App\Actions\Patients\StoreAttachment;
use App\Enums\AttachmentCategory;
use App\Models\Patient;
use App\Models\PatientAttachment;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Upload / list / delete files in a patient's file. Embeddable in the profile and the visit screen.
 */
class AttachmentManager extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $patientId;

    #[Locked]
    public ?int $visitId = null;

    /** @var list<TemporaryUploadedFile> */
    public array $files = [];

    public string $category = 'xray';

    public ?string $title = null;

    public ?string $taken_at = null;

    #[Url(as: 'category')]
    public string $filter = '';

    public bool $showUpload = false;

    public function mount(Patient $patient, ?int $visitId = null): void
    {
        $this->patientId = $patient->id;
        $this->visitId = $visitId;
    }

    protected function patient(): Patient
    {
        return Patient::withTrashed()->findOrFail($this->patientId);
    }

    public function upload(StoreAttachment $action): void
    {
        $patient = $this->patient();
        $this->authorize('create', [PatientAttachment::class, $patient]);

        $this->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'max:'.config('clinic.attachments.max_kb'), 'mimes:'.implode(',', config('clinic.attachments.mimes'))],
            'category' => ['required', Rule::enum(AttachmentCategory::class)],
            'title' => ['nullable', 'string', 'max:150'],
            'taken_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], attributes: ['files' => __('Files'), 'files.*' => __('File')]);

        foreach ($this->files as $file) {
            $action->handle($patient, $file, [
                'category' => $this->category,
                'title' => count($this->files) === 1 ? $this->title : null,
                'taken_at' => $this->taken_at,
                'visit_id' => $this->visitId,
            ], auth()->user());
        }

        $count = count($this->files);
        $this->reset('files', 'title', 'taken_at', 'showUpload');
        $this->dispatch('attachment-saved');
        $this->dispatch('toast', message: trans_choice(':count file uploaded.|:count files uploaded.', $count, ['count' => $count]));
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function delete(int $id): void
    {
        $attachment = PatientAttachment::where('patient_id', $this->patientId)->findOrFail($id);
        $this->authorize('delete', $attachment);

        activity('medical')->causedBy(auth()->user())->performedOn($this->patient())
            ->event('attachment_deleted')
            ->withProperties(['title' => $attachment->title, 'file' => $attachment->original_name])
            ->log('attachment deleted');

        $attachment->delete();
        $this->dispatch('toast', message: __('File deleted.'));
    }

    public function render()
    {
        $user = auth()->user();
        $patient = $this->patient();

        $attachments = PatientAttachment::query()
            ->where('patient_id', $this->patientId)
            ->when($this->visitId, fn ($q) => $q->where('visit_id', $this->visitId))
            ->when(AttachmentCategory::tryFrom($this->filter), fn ($q, $c) => $q->where('category', $c->value))
            ->with('uploader')
            ->latest()
            ->get();

        return view('livewire.patients.attachment-manager', [
            'attachments' => $attachments,
            'categories' => AttachmentCategory::options(),
            'canUpload' => $user->can('create', [PatientAttachment::class, $patient]),
            // Same patient for every file, so one check covers the whole list.
            'canOpen' => $user->can('attachments.view') && $patient->isMedicallyVisibleTo($user),
            'canDelete' => $user->can('attachments.delete'),
            'accept' => collect(config('clinic.attachments.mimes'))->map(fn ($ext) => '.'.$ext)->implode(','),
        ]);
    }
}
