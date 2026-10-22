<?php

namespace App\Models;

use App\Enums\ClinicSpecialty;
use App\Enums\CustomFieldEntity;
use App\Enums\CustomFieldType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['entity', 'key', 'label_ar', 'label_en', 'type', 'options', 'is_required', 'specialty', 'sort', 'is_active'])]
class CustomField extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'entity' => CustomFieldEntity::class,
            'type' => CustomFieldType::class,
            'specialty' => ClinicSpecialty::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? ($this->label_en ?: $this->label_ar) : $this->label_ar;
    }

    /** @return list<string> */
    public function optionList(): array
    {
        return array_values($this->options ?? []);
    }

    /** Active fields for an entity that apply to the clinic's current specialty. */
    public function scopeFor(Builder $query, CustomFieldEntity $entity): void
    {
        $query->where('entity', $entity->value)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('specialty')->orWhere('specialty', settings('specialty')))
            ->orderBy('sort')
            ->orderBy('id');
    }
}
