<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'starts_at', 'ends_at', 'reason', 'created_by'])]
class BlockedPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** Periods that overlap [start, end) for the doctor or the whole clinic. */
    public function scopeOverlapping(Builder $query, int $doctorId, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('doctor_id')->orWhere('doctor_id', $doctorId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }
}
