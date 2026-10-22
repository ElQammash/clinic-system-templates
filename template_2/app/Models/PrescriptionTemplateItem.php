<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prescription_template_id', 'medicine_id', 'medicine_name', 'dose', 'frequency', 'duration', 'instructions', 'sort'])]
class PrescriptionTemplateItem extends Model
{
    public function template(): BelongsTo
    {
        return $this->belongsTo(PrescriptionTemplate::class, 'prescription_template_id');
    }
}
