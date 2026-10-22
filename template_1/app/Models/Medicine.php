<?php

namespace App\Models;

use App\Enums\MedicineForm;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'generic_name', 'form', 'strength', 'default_dose', 'default_frequency', 'default_duration', 'notes', 'is_active'])]
class Medicine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'form' => MedicineForm::class,
            'is_active' => 'boolean',
            'times_prescribed' => 'integer',
        ];
    }

    /** "Augmentin 1g tablet" */
    public function displayName(): string
    {
        return trim(implode(' ', array_filter([$this->name, $this->strength])));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Name / generic name search, most prescribed first. */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.addcslashes($term, '%_').'%';
        $normalized = '%'.addcslashes(ArabicText::normalize($term), '%_').'%';

        $query->where(fn (Builder $q) => $q->where('name', 'like', $like)
            ->orWhere('generic_name', 'like', $like)
            ->orWhere('name', 'like', $normalized))
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [addcslashes($term, '%_').'%'])
            ->orderByDesc('times_prescribed');
    }
}
