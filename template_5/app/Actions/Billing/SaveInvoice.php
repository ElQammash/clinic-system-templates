<?php

namespace App\Actions\Billing;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoiceCalculator;
use App\Services\NumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates an invoice with its lines. Totals are always recalculated
 * from the lines on the server; client-side totals are display only.
 */
class SaveInvoice
{
    public function __construct(
        protected InvoiceCalculator $calculator,
        protected NumberSequence $sequence,
    ) {}

    /**
     * @param  array{patient_id: int, visit_id?: ?int, appointment_id?: ?int, doctor_id?: ?int, issued_at?: ?string, discount_type?: ?string, discount_value?: float|int|string|null, notes?: ?string}  $data  money in pounds
     * @param  list<array{service_id?: ?int, description: string, quantity: int|string, unit_price: float|int|string, discount?: float|int|string|null}>  $items  money in pounds
     */
    public function handle(?Invoice $invoice, array $data, array $items, User $actor): Invoice
    {
        if ($invoice?->isVoid()) {
            throw ValidationException::withMessages(['invoice' => __('A void invoice cannot be changed.')]);
        }

        $items = array_values(array_filter($items, fn ($item) => filled(trim((string) ($item['description'] ?? '')))));

        if ($items === []) {
            throw ValidationException::withMessages(['items' => __('Add at least one item to the invoice.')]);
        }

        $lines = array_map(fn ($item) => [
            'service_id' => $item['service_id'] ?? null,
            'description' => trim($item['description']),
            'quantity' => max(1, (int) $item['quantity']),
            'unit_price' => InvoiceCalculator::toMinor($item['unit_price']),
            'discount' => InvoiceCalculator::toMinor($item['discount'] ?? 0),
        ], $items);

        $discountType = DiscountType::tryFrom($data['discount_type'] ?? '') ?? DiscountType::Fixed;
        $discountValue = InvoiceCalculator::toMinor($data['discount_value'] ?? 0);
        $this->calculator->guardPercent($discountType, $discountValue);

        $totals = $this->calculator->calculate($lines, $discountType, $discountValue);

        return DB::transaction(function () use ($invoice, $data, $lines, $totals, $discountType, $discountValue, $actor) {
            $isNew = $invoice === null;

            if ($invoice) {
                $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            }

            $paid = $invoice ? (int) $invoice->getRawOriginal('paid_amount') : 0;

            if ($totals['total'] < $paid) {
                throw ValidationException::withMessages(['items' => __('The total cannot be less than what has already been paid (:amount).', ['amount' => money($paid / 100)])]);
            }

            $issuedAt = $data['issued_at'] ?? null;

            $invoice ??= new Invoice([
                'number' => $this->nextNumber(),
                'created_by' => $actor->id,
            ]);

            $invoice->fill([
                'patient_id' => $data['patient_id'],
                'visit_id' => $data['visit_id'] ?? $invoice->visit_id,
                'appointment_id' => $data['appointment_id'] ?? $invoice->appointment_id,
                'doctor_id' => $data['doctor_id'] ?? $invoice->doctor_id,
                'issued_at' => $issuedAt ?: ($invoice->issued_at ?? now()),
                'notes' => $data['notes'] ?? null,
            ]);

            // Raw minor units (bypassing the Money cast, which expects pounds).
            $invoice->setRawAttributes(array_merge($invoice->getAttributes(), [
                'subtotal' => $totals['subtotal'],
                'discount_type' => $discountType->value,
                'discount_value' => $discountValue,
                'discount_amount' => $totals['discount_amount'],
                'total' => $totals['total'],
                'paid_amount' => $paid,
                'balance' => $totals['total'] - $paid,
                'status' => InvoiceStatus::fromAmounts($totals['total'], $paid)->value,
            ]));

            $invoice->save();

            $invoice->items()->delete();
            foreach ($lines as $index => $line) {
                $item = $invoice->items()->make(['service_id' => $line['service_id'], 'description' => $line['description'], 'quantity' => $line['quantity'], 'sort' => $index]);
                $item->setRawAttributes(array_merge($item->getAttributes(), [
                    'unit_price' => $line['unit_price'],
                    'discount' => $line['discount'],
                    'total' => $totals['lines'][$index],
                ]));
                $item->save();
            }

            if (! $isNew) {
                activity('billing')->causedBy($actor)->performedOn($invoice)->event('updated')->log('invoice updated');
            }

            return $invoice->fresh(['items']);
        });
    }

    /** Yearly numbering: INV-2026-00001 */
    public function nextNumber(): string
    {
        $year = now()->year;

        return $this->sequence->formatted("invoice:{$year}", "INV-{$year}-", 5);
    }
}
