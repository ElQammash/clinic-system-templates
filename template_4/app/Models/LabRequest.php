<?php

namespace App\Models;

use App\Enums\LabRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['patient_id', 'visit_id', 'doctor_id', 'status', 'notes', 'result_summary', 'requested_at', 'completed_at'])]
class LabRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => LabRequestStatus::class,
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class)->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientAttachment::class);
    }
}
