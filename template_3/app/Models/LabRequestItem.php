<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lab_request_id', 'lab_test_id', 'name', 'result'])]
class LabRequestItem extends Model
{
    public function request(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'lab_request_id');
    }
}
