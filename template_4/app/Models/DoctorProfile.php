<?php

namespace App\Models;

use App\Casts\Money;
use App\Enums\ClinicSpecialty;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'title_ar', 'title_en', 'specialty', 'color', 'slot_minutes', 'consultation_fee', 'license_number', 'signature_path'])]
class DoctorProfile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'specialty' => ClinicSpecialty::class,
            'slot_minutes' => 'integer',
            'consultation_fee' => Money::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function title(): ?string
    {
        return app()->getLocale() === 'en' ? ($this->title_en ?: $this->title_ar) : ($this->title_ar ?: $this->title_en);
    }
}
