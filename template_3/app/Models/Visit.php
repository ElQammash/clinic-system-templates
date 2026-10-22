<?php

namespace App\Models;

use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'patient_id', 'doctor_id', 'appointment_id', 'visited_at', 'chief_complaint', 'examination', 'diagnosis', 'notes',
    'bp_systolic', 'bp_diastolic', 'pulse', 'temperature', 'spo2', 'weight', 'height', 'custom_data',
    'follow_up_date', 'follow_up_appointment_id', 'completed_at',
])]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'completed_at' => 'datetime',
            'follow_up_date' => 'date',
            'custom_data' => 'array',
            'temperature' => 'float',
            'weight' => 'float',
            'height' => 'float',
            'bmi' => 'float',
            'bp_systolic' => 'integer',
            'bp_diastolic' => 'integer',
            'pulse' => 'integer',
            'spo2' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Visit $visit) => $visit->bmi = self::calculateBmi($visit->weight, $visit->height));
    }

    /** BMI = kg / m². Null unless both weight and a plausible height are known. */
    public static function calculateBmi(?float $weightKg, ?float $heightCm): ?float
    {
        if (! $weightKg || ! $heightCm || $heightCm < 30) {
            return null;
        }

        return round($weightKg / (($heightCm / 100) ** 2), 1);
    }

    /** WHO adult categories. */
    public static function bmiCategory(?float $bmi): ?array
    {
        return match (true) {
            $bmi === null => null,
            $bmi < 18.5 => ['label' => __('Underweight'), 'color' => 'sky'],
            $bmi < 25 => ['label' => __('Normal'), 'color' => 'emerald'],
            $bmi < 30 => ['label' => __('Overweight'), 'color' => 'amber'],
            default => ['label' => __('Obese'), 'color' => 'rose'],
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('medical')
            ->logOnly(['chief_complaint', 'examination', 'diagnosis', 'notes', 'follow_up_date'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function followUpAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'follow_up_appointment_id');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class)->latestOfMany();
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientAttachment::class);
    }

    public function hasVitals(): bool
    {
        return collect([$this->bp_systolic, $this->pulse, $this->temperature, $this->spo2, $this->weight, $this->height])->filter()->isNotEmpty();
    }

    public function bloodPressure(): ?string
    {
        return $this->bp_systolic && $this->bp_diastolic ? "{$this->bp_systolic}/{$this->bp_diastolic}" : null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
