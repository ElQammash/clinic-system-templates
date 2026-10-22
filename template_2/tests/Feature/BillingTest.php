<?php

use App\Actions\Billing\ManagePayments;
use App\Actions\Billing\SaveInvoice;
use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Livewire\Billing\InvoiceEditor;
use App\Livewire\Billing\InvoiceShow;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\VisitType;
use Database\Seeders\BillingSeeder;
use Database\Seeders\SchedulingSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([SchedulingSeeder::class, BillingSeeder::class]);
    $this->patient = Patient::factory()->create();
});

function makeInvoice(array $items = [['description' => 'كشف', 'quantity' => 1, 'unit_price' => 300]], array $data = []): Invoice
{
    return app(SaveInvoice::class)->handle(null, array_merge(['patient_id' => test()->patient->id], $data), $items, auth()->user());
}

it('creates an invoice with yearly sequential numbers and correct totals', function () {
    actingAsRole(Role::Receptionist);

    $first = makeInvoice([
        ['description' => 'كشف', 'quantity' => 1, 'unit_price' => 300],
        ['description' => 'حقنة', 'quantity' => 2, 'unit_price' => '50.25'],
    ], ['discount_type' => 'percent', 'discount_value' => 10]);
    $second = makeInvoice();

    $year = now()->year;
    expect($first->number)->toBe("INV-{$year}-00001")
        ->and($second->number)->toBe("INV-{$year}-00002")
        ->and($first->subtotal)->toBe(400.5)
        ->and($first->discount_amount)->toBe(40.05)
        ->and($first->total)->toBe(360.45)
        ->and($first->balance)->toBe(360.45)
        ->and($first->status)->toBe(InvoiceStatus::Unpaid)
        ->and($first->items)->toHaveCount(2);
});

it('tracks partial and full payments', function () {
    $user = actingAsRole(Role::Receptionist);
    $invoice = makeInvoice([['description' => 'كشف', 'quantity' => 1, 'unit_price' => 300]]);
    $payments = app(ManagePayments::class);

    $payments->pay($invoice, 100, PaymentMethod::Cash, $user);
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->paid_amount)->toBe(100.0)
        ->and($invoice->balance)->toBe(200.0)
        ->and($this->patient->balance())->toBe(200.0);

    $receipt = $payments->pay($invoice, 200, PaymentMethod::InstaPay, $user, 'IPN-123');
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->balance)->toBe(0.0)
        ->and($receipt->number)->toStartWith('RC-')
        ->and($receipt->reference)->toBe('IPN-123');
});

it('rejects overpayment and zero payments', function () {
    $user = actingAsRole(Role::Receptionist);
    $invoice = makeInvoice();

    expect(fn () => app(ManagePayments::class)->pay($invoice, 300.01, PaymentMethod::Cash, $user))->toThrow(ValidationException::class)
        ->and(fn () => app(ManagePayments::class)->pay($invoice, 0, PaymentMethod::Cash, $user))->toThrow(ValidationException::class);
});

it('recalculates the balance when a payment is deleted', function () {
    $user = actingAsRole(Role::Admin);
    $invoice = makeInvoice();
    $payment = app(ManagePayments::class)->pay($invoice, 300, PaymentMethod::Cash, $user);

    app(ManagePayments::class)->deletePayment($payment, $user);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->fresh()->balance)->toBe(300.0);
    $this->assertDatabaseHas('activity_log', ['event' => 'payment_deleted', 'subject_id' => $invoice->id]);
});

it('does not let the total drop below what was paid', function () {
    $user = actingAsRole(Role::Admin);
    $invoice = makeInvoice();
    app(ManagePayments::class)->pay($invoice, 250, PaymentMethod::Cash, $user);

    expect(fn () => app(SaveInvoice::class)->handle($invoice, ['patient_id' => $this->patient->id], [['description' => 'كشف', 'quantity' => 1, 'unit_price' => 200]], $user))
        ->toThrow(ValidationException::class);

    // Raising the total is fine and keeps the payment.
    $updated = app(SaveInvoice::class)->handle($invoice, ['patient_id' => $this->patient->id], [['description' => 'كشف', 'quantity' => 1, 'unit_price' => 400]], $user);
    expect($updated->balance)->toBe(150.0)->and($updated->status)->toBe(InvoiceStatus::PartiallyPaid);
});

it('voids unpaid invoices only, removing them from the balance', function () {
    $user = actingAsRole(Role::Accountant);
    $paid = makeInvoice();
    app(ManagePayments::class)->pay($paid, 50, PaymentMethod::Cash, $user);
    $unpaid = makeInvoice();

    expect(fn () => app(ManagePayments::class)->void($paid, 'خطأ', $user))->toThrow(ValidationException::class);

    app(ManagePayments::class)->void($unpaid, 'تم الإدخال بالخطأ', $user);

    expect($unpaid->fresh()->status)->toBe(InvoiceStatus::Void)
        ->and($unpaid->fresh()->balance)->toBe(0.0)
        ->and($this->patient->balance())->toBe(250.0);
    expect(fn () => app(ManagePayments::class)->pay($unpaid->fresh(), 10, PaymentMethod::Cash, $user))->toThrow(ValidationException::class);
});

it('lets reception create invoices and take payments but not void them', function () {
    $user = actingAsRole(Role::Receptionist);
    $invoice = makeInvoice();

    expect($user->can('pay', $invoice))->toBeTrue()
        ->and($user->can('void', $invoice))->toBeFalse()
        ->and($user->can('delete', $invoice))->toBeFalse()
        ->and($user->can('payments.delete'))->toBeFalse();

    Livewire::test(InvoiceShow::class, ['invoice' => $invoice])
        ->call('openPayment')
        ->assertSet('amount', 300.0)
        ->set('method', 'wallet')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertDispatched('open-receipt');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    Livewire::test(InvoiceShow::class, ['invoice' => $invoice])->set('voidReason', 'x')->call('void')->assertForbidden();
});

it('does not let doctors create invoices or see other doctors\' invoices', function () {
    $doctor = actingAsRole(Role::Doctor);
    $mine = Invoice::withoutEvents(fn () => makeInvoice(data: ['doctor_id' => $doctor->id]));
    $other = makeInvoice();

    $this->get(route('invoices.create'))->assertForbidden();
    $this->get(route('invoices.show', $mine))->assertOk();
    $this->get(route('invoices.show', $other))->assertForbidden();
});

it('prefills the invoice from the appointment visit type and prevents duplicates', function () {
    actingAsRole(Role::Receptionist);
    $type = VisitType::where('name_ar', 'كشف')->firstOrFail();
    $appointment = Appointment::factory()->status(AppointmentStatus::Completed)->create(['visit_type_id' => $type->id, 'patient_id' => $this->patient->id]);

    $this->get(route('invoices.create', ['appointment' => $appointment->id]))->assertOk();

    Livewire::withQueryParams(['appointment' => $appointment->id])
        ->test(InvoiceEditor::class)
        ->assertSet('patientId', $this->patient->id)
        ->assertSet('items.0.description', 'كشف')
        ->assertSet('items.0.unit_price', 300.0)
        ->call('save')
        ->assertHasNoErrors();

    $invoice = Invoice::where('appointment_id', $appointment->id)->firstOrFail();
    expect($invoice->doctor_id)->toBe($appointment->doctor_id);

    Livewire::withQueryParams(['appointment' => $appointment->id])
        ->test(InvoiceEditor::class)
        ->assertRedirect(route('invoices.show', $invoice));
});

it('prints invoices and receipts', function () {
    $user = actingAsRole(Role::Receptionist);
    $invoice = makeInvoice();
    $payment = app(ManagePayments::class)->pay($invoice, 300, PaymentMethod::Card, $user);

    $this->get(route('print.invoice', $invoice))->assertOk()->assertSee($invoice->number);
    $this->get(route('print.receipt', $payment))->assertOk()->assertSee($payment->number);
    $this->get(route('print.invoice', [$invoice, 'format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('logs deleted invoices', function () {
    $user = actingAsRole(Role::Admin);
    $invoice = makeInvoice();

    Livewire::test(InvoiceShow::class, ['invoice' => $invoice])->call('delete')->assertRedirect(route('invoices.index'));

    expect($invoice->fresh()->trashed())->toBeTrue();
    $this->assertDatabaseHas('activity_log', ['event' => 'deleted', 'log_name' => 'billing', 'causer_id' => $user->id]);
});

it('keeps two cashiers from collecting the same balance twice', function () {
    $user = actingAsRole(Role::Receptionist);
    $invoice = makeInvoice();
    $stale = Invoice::find($invoice->id); // loaded before the first payment

    app(ManagePayments::class)->pay($invoice, 300, PaymentMethod::Cash, $user);

    expect(fn () => app(ManagePayments::class)->pay($stale, 300, PaymentMethod::Cash, $user))->toThrow(ValidationException::class)
        ->and(Payment::count())->toBe(1);
});
