<?php

namespace App\Livewire\Billing;

use App\Actions\Billing\SaveInvoice;
use App\Enums\DiscountType;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Visit;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Create / edit an invoice. New invoices can be prefilled from ?appointment=, ?visit= or ?patient=.
 */
#[Title('Invoice')]
class InvoiceEditor extends Component
{
    #[Locked]
    public ?int $invoiceId = null;

    public ?int $patientId = null;

    #[Locked]
    public ?int $visitId = null;

    #[Locked]
    public ?int $appointmentId = null;

    #[Locked]
    public ?int $doctorId = null;

    public string $patientSearch = '';

    /** @var list<array{service_id: ?int, description: string, quantity: int|string, unit_price: float|int|string, discount: float|int|string}> */
    public array $items = [];

    public string $discountType = 'fixed';

    public float|int|string $discountValue = 0;

    public ?string $notes = null;

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice?->exists) {
            $this->authorize('update', $invoice);
            $this->fillFromInvoice($invoice->load('items'));

            return;
        }

        $this->authorize('create', Invoice::class);
        $this->prefill();
    }

    protected function fillFromInvoice(Invoice $invoice): void
    {
        $this->invoiceId = $invoice->id;
        $this->patientId = $invoice->patient_id;
        $this->visitId = $invoice->visit_id;
        $this->appointmentId = $invoice->appointment_id;
        $this->doctorId = $invoice->doctor_id;
        $this->discountType = $invoice->discount_type->value;
        $this->discountValue = $invoice->discount_value;
        $this->notes = $invoice->notes;
        $this->items = $invoice->items->map(fn ($item) => [
            'service_id' => $item->service_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'discount' => $item->discount,
        ])->all();
    }

    /** Prefill a new invoice from the appointment / visit it is created for. */
    protected function prefill(): void
    {
        $appointment = request()->integer('appointment') ? Appointment::with('visitType.service', 'doctor.doctorProfile')->find(request()->integer('appointment')) : null;
        $visit = request()->integer('visit') ? Visit::with('appointment.visitType.service', 'doctor.doctorProfile')->find(request()->integer('visit')) : null;

        $appointment ??= $visit?->appointment;
        $this->visitId = $visit?->id ?? Visit::query()->where('appointment_id', $appointment?->id)->value('id');
        $this->appointmentId = $appointment?->id;
        $this->patientId = $visit?->patient_id ?? $appointment?->patient_id ?? (request()->integer('patient') ?: null);
        $this->doctorId = $visit?->doctor_id ?? $appointment?->doctor_id;

        // Don't create a second invoice for the same appointment by accident.
        if ($appointment && ($existing = Invoice::query()->notVoid()->where('appointment_id', $appointment->id)->first())) {
            session()->flash('toast', __('This appointment already has an invoice.'));
            $this->redirectRoute('invoices.show', $existing, navigate: true);

            return;
        }

        $type = $appointment?->visitType;
        $doctor = $visit?->doctor ?? $appointment?->doctor;

        if ($type) {
            $this->items[] = [
                'service_id' => $type->service_id,
                'description' => $type->service?->name() ?? $type->name(),
                'quantity' => 1,
                'unit_price' => $type->service?->price ?? $type->default_price,
                'discount' => 0,
            ];
        } elseif ($doctor?->doctorProfile?->consultation_fee) {
            $this->items[] = ['service_id' => null, 'description' => __('Consultation'), 'quantity' => 1, 'unit_price' => $doctor->doctorProfile->consultation_fee, 'discount' => 0];
        }

        if ($this->items === []) {
            $this->addItem();
        }
    }

    #[Computed]
    public function patient(): ?Patient
    {
        return $this->patientId ? Patient::withTrashed()->find($this->patientId) : null;
    }

    #[Computed]
    public function patientResults(): Collection
    {
        return mb_strlen(trim($this->patientSearch)) >= 2 ? Patient::query()->search($this->patientSearch)->limit(6)->get() : collect();
    }

    public function selectPatient(int $id): void
    {
        $this->patientId = $id;
        $this->patientSearch = '';
    }

    public function addItem(?int $serviceId = null): void
    {
        $service = $serviceId ? Service::find($serviceId) : null;

        $this->items[] = [
            'service_id' => $service?->id,
            'description' => $service?->name() ?? '',
            'quantity' => 1,
            'unit_price' => $service?->price ?? 0,
            'discount' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /** Live totals for display (the server recalculates on save). */
    #[Computed]
    public function totals(): array
    {
        $lines = array_map(fn ($item) => [
            'quantity' => is_numeric($item['quantity']) ? (int) $item['quantity'] : 1,
            'unit_price' => InvoiceCalculator::toMinor(is_numeric($item['unit_price']) ? $item['unit_price'] : 0),
            'discount' => InvoiceCalculator::toMinor(is_numeric($item['discount']) ? $item['discount'] : 0),
        ], $this->items);

        $result = app(InvoiceCalculator::class)->calculate(
            $lines,
            DiscountType::tryFrom($this->discountType) ?? DiscountType::Fixed,
            InvoiceCalculator::toMinor(is_numeric($this->discountValue) ? $this->discountValue : 0),
        );

        return array_map(fn ($v) => is_array($v) ? array_map(fn ($x) => $x / 100, $v) : $v / 100, $result);
    }

    public function save(SaveInvoice $action): void
    {
        $invoice = $this->invoiceId ? Invoice::findOrFail($this->invoiceId) : null;
        $invoice ? $this->authorize('update', $invoice) : $this->authorize('create', Invoice::class);

        $this->validate([
            'patientId' => ['required', Rule::exists('patients', 'id')],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'discountType' => ['required', Rule::enum(DiscountType::class)],
            'discountValue' => ['nullable', 'numeric', 'min:0', $this->discountType === 'percent' ? 'max:100' : 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'patientId' => __('Patient'),
            'items.*.description' => __('Description'),
            'items.*.unit_price' => __('Price'),
            'items.*.quantity' => __('Quantity'),
            'discountValue' => __('Discount'),
        ]);

        $saved = $action->handle($invoice, [
            'patient_id' => $this->patientId,
            'visit_id' => $this->visitId,
            'appointment_id' => $this->appointmentId,
            'doctor_id' => $this->doctorId,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue ?: 0,
            'notes' => $this->notes,
        ], $this->items, auth()->user());

        session()->flash('toast', $invoice ? __('Invoice updated.') : __('Invoice :number created.', ['number' => $saved->number]));
        $this->redirectRoute('invoices.show', $saved, navigate: true);
    }

    public function render()
    {
        return view('livewire.billing.invoice-editor', [
            'services' => Service::query()->active()->get(),
        ]);
    }
}
