<?php

namespace App\Services\Billing;

use App\Enums\DiscountType;
use Illuminate\Validation\ValidationException;

/**
 * Pure invoice arithmetic on integer minor units (piastres). No floats are
 * stored: inputs in pounds are converted once with toMinor().
 *
 *   line total   = quantity × unit price − line discount   (never below 0)
 *   subtotal     = Σ line totals
 *   discount     = fixed amount, or subtotal × percent (rounded half-up), capped at subtotal
 *   total        = subtotal − discount
 *   balance      = total − paid
 */
class InvoiceCalculator
{
    public static function toMinor(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * @param  list<array{quantity: int|string, unit_price: int, discount?: int}>  $lines  money in minor units
     * @param  int  $discountValue  fixed: minor units; percent: hundredths of a percent (1250 = 12.5%)
     * @return array{lines: list<int>, subtotal: int, discount_amount: int, total: int}
     */
    public function calculate(array $lines, DiscountType $discountType = DiscountType::Fixed, int $discountValue = 0): array
    {
        $lineTotals = [];

        foreach ($lines as $line) {
            $quantity = max(1, (int) $line['quantity']);
            $gross = $quantity * (int) $line['unit_price'];
            $lineTotals[] = max(0, $gross - (int) ($line['discount'] ?? 0));
        }

        $subtotal = array_sum($lineTotals);

        $discount = match ($discountType) {
            DiscountType::Fixed => $discountValue,
            // hundredths of a percent: subtotal × value / 10000, rounded half-up
            DiscountType::Percent => intdiv($subtotal * min($discountValue, 10000) + 5000, 10000),
        };

        $discount = min(max(0, $discount), $subtotal);

        return [
            'lines' => $lineTotals,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total' => $subtotal - $discount,
        ];
    }

    public function guardPercent(DiscountType $type, int $value): void
    {
        if ($type === DiscountType::Percent && $value > 10000) {
            throw ValidationException::withMessages(['discount_value' => __('The discount cannot be more than 100%.')]);
        }
    }
}
