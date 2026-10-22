<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Billing\InvoiceCalculator;
use App\Services\NumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Payments, voids and deletions. Each runs in a transaction with the invoice
 * row locked, so two cashiers can never over-collect the same invoice.
 */
class ManagePayments
{
    public function __construct(protected NumberSequence $sequence) {}

    /** @param  float|int|string  $amount  pounds */
    public function pay(Invoice $invoice, float|int|string $amount, PaymentMethod $method, User $actor, ?string $reference = null, ?string $notes = null, ?string $paidAt = null): Payment
    {
        $amountMinor = InvoiceCalculator::toMinor($amount);

        if ($amountMinor <= 0) {
            throw ValidationException::withMessages(['amount' => __('The amount must be greater than zero.')]);
        }

        return DB::transaction(function () use ($invoice, $amountMinor, $method, $actor, $reference, $notes, $paidAt) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->isVoid()) {
                throw ValidationException::withMessages(['amount' => __('A void invoice cannot be paid.')]);
            }

            $balance = (int) $invoice->getRawOriginal('balance');
            if ($amountMinor > $balance) {
                throw ValidationException::withMessages(['amount' => __('The amount is more than the remaining balance (:balance).', ['balance' => money($balance / 100)])]);
            }

            $year = now()->year;
            $payment = new Payment([
                'number' => $this->sequence->formatted("receipt:{$year}", "RC-{$year}-", 5),
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'method' => $method,
                'reference' => $reference,
                'paid_at' => $paidAt ?: now(),
                'received_by' => $actor->id,
                'notes' => $notes,
            ]);
            $payment->setRawAttributes(array_merge($payment->getAttributes(), ['amount' => $amountMinor]));
            $payment->save();

            $this->recalculate($invoice);

            return $payment;
        });
    }

    public function deletePayment(Payment $payment, User $actor): void
    {
        DB::transaction(function () use ($payment, $actor) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);

            activity('billing')->causedBy($actor)->performedOn($invoice)->event('payment_deleted')
                ->withProperties(['payment' => $payment->number, 'amount' => $payment->amount, 'method' => $payment->method->value])
                ->log('payment deleted');

            $payment->delete();
            $this->recalculate($invoice);
        });
    }

    /** Void keeps the invoice for the records but removes it from all balances and revenue. */
    public function void(Invoice $invoice, string $reason, User $actor): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason, $actor) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->payments()->exists()) {
                throw ValidationException::withMessages(['void' => __('Delete the payments of this invoice before voiding it.')]);
            }

            $invoice->forceFill([
                'status' => InvoiceStatus::Void,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->setRawAttributes(array_merge($invoice->getAttributes(), ['balance' => 0]))->save();

            activity('billing')->causedBy($actor)->performedOn($invoice)->event('voided')->withProperties(['reason' => $reason])->log('invoice voided');

            return $invoice;
        });
    }

    public function delete(Invoice $invoice, User $actor): void
    {
        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages(['delete' => __('An invoice with payments cannot be deleted.')]);
        }

        activity('billing')->causedBy($actor)->performedOn($invoice)->event('deleted')
            ->withProperties(['number' => $invoice->number, 'total' => $invoice->total])
            ->log('invoice deleted');

        $invoice->delete();
    }

    /** Recompute paid / balance / status from the stored payments (minor units). */
    public function recalculate(Invoice $invoice): Invoice
    {
        $paid = (int) Payment::query()->where('invoice_id', $invoice->id)->sum('amount');
        $total = (int) $invoice->getRawOriginal('total');

        $invoice->setRawAttributes(array_merge($invoice->getAttributes(), [
            'paid_amount' => $paid,
            'balance' => max(0, $total - $paid),
            'status' => $invoice->isVoid() ? InvoiceStatus::Void->value : InvoiceStatus::fromAmounts($total, $paid)->value,
        ]));
        $invoice->save();

        return $invoice;
    }
}
