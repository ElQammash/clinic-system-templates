<?php

namespace App\Models;

use App\Casts\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name_ar', 'name_en', 'category', 'price', 'is_active', 'sort'])]
class Service extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => Money::class,
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function name(): string
    {
        return app()->getLocale() === 'en' ? ($this->name_en ?: $this->name_ar) : $this->name_ar;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('name_ar');
    }
}
