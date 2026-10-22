<?php

use App\Enums\DiscountType;
use App\Services\Billing\InvoiceCalculator;

beforeEach(fn () => $this->calc = new InvoiceCalculator);

it('multiplies quantity by price and sums the lines', function () {
    $result = $this->calc->calculate([
        ['quantity' => 2, 'unit_price' => 15000],
        ['quantity' => 1, 'unit_price' => 30000],
    ]);

    expect($result)->toBe(['lines' => [30000, 30000], 'subtotal' => 60000, 'discount_amount' => 0, 'total' => 60000]);
});

it('applies line discounts without going below zero', function () {
    $result = $this->calc->calculate([
        ['quantity' => 1, 'unit_price' => 30000, 'discount' => 5000],
        ['quantity' => 1, 'unit_price' => 1000, 'discount' => 5000],
    ]);

    expect($result['lines'])->toBe([25000, 0])->and($result['total'])->toBe(25000);
});

it('applies a fixed invoice discount capped at the subtotal', function () {
    expect($this->calc->calculate([['quantity' => 1, 'unit_price' => 30000]], DiscountType::Fixed, 5000)['total'])->toBe(25000)
        ->and($this->calc->calculate([['quantity' => 1, 'unit_price' => 30000]], DiscountType::Fixed, 99999)['total'])->toBe(0);
});

it('applies percentage discounts with half-up rounding to the piastre', function () {
    // 12.5% of 333.33 = 41.66625 -> 41.67
    $result = $this->calc->calculate([['quantity' => 1, 'unit_price' => 33333]], DiscountType::Percent, 1250);

    expect($result['discount_amount'])->toBe(4167)->and($result['total'])->toBe(29166);

    expect($this->calc->calculate([['quantity' => 1, 'unit_price' => 30000]], DiscountType::Percent, 10000)['total'])->toBe(0);
});

it('converts pounds to piastres without float errors', function () {
    expect(InvoiceCalculator::toMinor('0.1'))->toBe(10)
        ->and(InvoiceCalculator::toMinor(19.99))->toBe(1999)
        ->and(InvoiceCalculator::toMinor('1000.005'))->toBe(100001)
        ->and(InvoiceCalculator::toMinor(null))->toBe(0);
});
