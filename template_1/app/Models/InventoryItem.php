<?php

namespace App\Models;

use App\Casts\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'unit', 'quantity', 'reorder_level', 'unit_cost', 'is_active'])]
class InventoryItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'unit_cost' => Money::class,
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function isLow(): bool
    {
        return $this->reorder_level > 0 && $this->quantity <= $this->reorder_level;
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->where('is_active', true)->where('reorder_level', '>', 0)->whereColumn('quantity', '<=', 'reorder_level');
    }
}
