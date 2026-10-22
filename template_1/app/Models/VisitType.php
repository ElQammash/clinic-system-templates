<?php

namespace App\Models;

use App\Casts\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name_ar', 'name_en', 'service_id', 'default_price', 'default_duration', 'color', 'is_active', 'sort'])]
class VisitType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_price' => Money::class,
            'default_duration' => 'integer',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function name(): string
    {
        return app()->getLocale() === 'en' ? ($this->name_en ?: $this->name_ar) : $this->name_ar;
    }

    /** Duration for a doctor: the visit type's own duration or the doctor's slot length. */
    public function durationFor(User $doctor): int
    {
        return $this->default_duration ?: ($doctor->doctorProfile?->slot_minutes ?? 15);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }
}
