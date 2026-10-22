<?php

namespace App\Actions\Inventory;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStock
{
    /**
     * @param  string  $type  in (received) | out (used) | adjust (stock count: $quantity is the new total)
     */
    public function handle(InventoryItem $item, string $type, int $quantity, ?string $reason, User $actor): StockMovement
    {
        if ($quantity < 0 || ($type !== 'adjust' && $quantity === 0)) {
            throw ValidationException::withMessages(['quantity' => __('Enter a valid quantity.')]);
        }

        return DB::transaction(function () use ($item, $type, $quantity, $reason, $actor) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $change = match ($type) {
                'in' => $quantity,
                'out' => -$quantity,
                'adjust' => $quantity - $item->quantity,
                default => throw ValidationException::withMessages(['type' => __('Unknown movement type.')]),
            };

            if ($item->quantity + $change < 0) {
                throw ValidationException::withMessages(['quantity' => __('Not enough stock: only :qty left.', ['qty' => $item->quantity])]);
            }

            $item->update(['quantity' => $item->quantity + $change]);

            return $item->movements()->create([
                'type' => $type,
                'quantity' => $change,
                'balance_after' => $item->quantity,
                'reason' => $reason,
                'user_id' => $actor->id,
            ]);
        });
    }
}
