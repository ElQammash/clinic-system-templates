<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['doctor_id', 'name', 'notes'])]
class PrescriptionTemplate extends Model
{
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionTemplateItem::class)->orderBy('sort');
    }

    /** Shared templates plus the doctor's own. */
    public function scopeAvailableTo(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('doctor_id')->orWhere('doctor_id', $user->id));
    }
}
