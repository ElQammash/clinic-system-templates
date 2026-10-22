<?php

namespace App\Actions\Patients;

use App\Enums\AttachmentCategory;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreAttachment
{
    /**
     * @param  array{category: string, title?: ?string, taken_at?: ?string, visit_id?: ?int, lab_request_id?: ?int}  $meta
     */
    public function handle(Patient $patient, UploadedFile $file, array $meta, User $actor): PatientAttachment
    {
        $disk = config('clinic.attachments.disk');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = $file->storeAs("patients/{$patient->id}", Str::uuid().'.'.$extension, $disk);

        $attachment = $patient->attachments()->create([
            'category' => AttachmentCategory::from($meta['category']),
            'title' => filled($meta['title'] ?? null) ? $meta['title'] : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'taken_at' => $meta['taken_at'] ?? null,
            'visit_id' => $meta['visit_id'] ?? null,
            'lab_request_id' => $meta['lab_request_id'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => $actor->id,
        ]);

        activity('medical')->causedBy($actor)->performedOn($patient)
            ->event('attachment_uploaded')
            ->withProperties(['attachment_id' => $attachment->id, 'title' => $attachment->title])
            ->log('attachment uploaded');

        return $attachment;
    }
}
