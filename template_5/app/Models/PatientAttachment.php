<?php

namespace App\Models;

use App\Enums\AttachmentCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

#[Fillable(['patient_id', 'visit_id', 'lab_request_id', 'category', 'title', 'disk', 'path', 'original_name', 'mime', 'size', 'taken_at', 'uploaded_by'])]
class PatientAttachment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => AttachmentCategory::class,
            'taken_at' => 'date',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Remove the stored file when the record is deleted.
        static::deleted(fn (PatientAttachment $attachment) => Storage::disk($attachment->disk)->delete($attachment->path));
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }
}
