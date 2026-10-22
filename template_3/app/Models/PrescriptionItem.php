<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prescription_id', 'medicine_id', 'medicine_name', 'dose', 'frequency', 'duration', 'instructions', 'sort'])]
class PrescriptionItem extends Model
{
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /** "1 tablet · every 8 hours · 5 days" */
    public function directions(): string
    {
        return collect([$this->dose, $this->frequency, $this->duration])->filter()->implode(' — ');
    }
}
