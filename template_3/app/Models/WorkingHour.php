<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'day_of_week', 'starts_at', 'ends_at'])]
class WorkingHour extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** "17:00:00" -> "17:00" */
    public function startTime(): string
    {
        return substr($this->starts_at, 0, 5);
    }

    public function endTime(): string
    {
        return substr($this->ends_at, 0, 5);
    }
}
