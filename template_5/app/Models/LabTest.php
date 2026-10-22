<?php

namespace App\Models;

use App\Enums\LabTestType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'type', 'is_active'])]
class LabTest extends Model
{
    protected function casts(): array
    {
        return [
            'type' => LabTestType::class,
            'is_active' => 'boolean',
        ];
    }
}
