<?php

namespace App\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'patient_id', 'doctor_id', 'visit_type_id', 'starts_at', 'ends_at', 'status', 'source', 'queue_number',
    'confirmed_at', 'checked_in_at', 'started_at', 'completed_at', 'cancelled_at', 'cancel_reason',
    'reminder_sent_at', 'notes', 'created_by',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'source' => AppointmentSource::class,
            'queue_number' => 'integer',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('appointments')
            ->logOnly(['starts_at', 'ends_at', 'status', 'doctor_id', 'cancel_reason'])
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

    public function visitType(): BelongsTo
    {
        return $this->belongsTo(VisitType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function durationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /** Minutes the patient has been waiting since check-in (for the queue screen). */
    public function waitingMinutes(): ?int
    {
        return $this->checked_in_at ? (int) $this->checked_in_at->diffInMinutes(now()) : null;
    }

    public function isActive(): bool
    {
        return $this->status->blocksSlot();
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', AppointmentStatus::activeValues());
    }

    public function scopeOnDate(Builder $query, CarbonInterface|string $date): void
    {
        $date = Carbon::parse($date);
        $query->whereBetween('starts_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
    }

    public function scopeBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
    }

    /** Appointments the user may see: own schedule for doctors, everything with appointments.view_all. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->can('appointments.view_all')) {
            $query->where('doctor_id', $user->id);
        }
    }
}
