<?php

namespace App\Livewire\Billing;

use App\Actions\Billing\ManagePayments;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Validation\Rule;
use Livewire\Component;

class InvoiceShow extends Component
{
    public Invoice $invoice;

    public bool $showPayment = false;

    public float|int|string $amount = 0;

    public string $method = 'cash';

    public ?string $reference = null;

    public ?string $paymentNotes = null;

    public bool $showVoid = false;

    public string $voidReason = '';

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoice = $invoice;
    }

    public function openPayment(): void
    {
        $this->authorize('pay', $this->invoice);

        $this->amount = $this->invoice->fresh()->balance;
        $this->method = PaymentMethod::Cash->value;
        $this->reset('reference', 'paymentNotes');
        $this->resetValidation();
        $this->showPayment = true;
    }

    public function pay(ManagePayments $action): void
    {
        $this->authorize('pay', $this->invoice);

        $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'paymentNotes' => ['nullable', 'string', 'max:255'],
        ], attributes: ['amount' => __('Amount')]);

        $payment = $action->pay($this->invoice, $this->amount, PaymentMethod::from($this->method), auth()->user(), $this->reference, $this->paymentNotes);

        $this->showPayment = false;
        $this->invoice->refresh();
        $this->dispatch('toast', message: __('Payment :number recorded.', ['number' => $payment->number]));
        $this->dispatch('open-receipt', url: route('print.receipt', $payment));
    }

    public function deletePayment(int $id, ManagePayments $action): void
    {
        $this->authorize('payments.delete');
        $payment = Payment::where('invoice_id', $this->invoice->id)->findOrFail($id);

        $action->deletePayment($payment, auth()->user());

        $this->invoice->refresh();
        $this->dispatch('toast', message: __('Payment deleted.'));
    }

    public function void(ManagePayments $action): void
    {
        $this->authorize('void', $this->invoice);
        $this->validate(['voidReason' => ['required', 'string', 'max:255']], attributes: ['voidReason' => __('Reason')]);

        $action->void($this->invoice, $this->voidReason, auth()->user());

        $this->showVoid = false;
        $this->invoice->refresh();
        $this->dispatch('toast', message: __('Invoice voided.'));
    }

    public function delete(ManagePayments $action): void
    {
        $this->authorize('delete', $this->invoice);
        $action->delete($this->invoice, auth()->user());

        session()->flash('toast', __('Invoice deleted.'));
        $this->redirectRoute('invoices.index', navigate: true);
    }

    public function render()
    {
        $this->invoice->load(['items', 'payments.receiver', 'patient', 'doctor', 'creator']);

        return view('livewire.billing.invoice-show', [
            'methods' => PaymentMethod::options(),
            'patientBalance' => $this->invoice->patient->balance(),
        ])->title($this->invoice->number);
    }
}
