<?php

use App\Actions\Billing\ManagePayments;
use App\Actions\Billing\SaveInvoice;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Livewire\Dashboard;
use App\Livewire\Reports\Index as Reports;
use App\Models\Appointment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use App\Models\User;
use App\Services\Reports\ReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\BillingSeeder;
use Database\Seeders\SchedulingSeeder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([SchedulingSeeder::class, BillingSeeder::class]);
    $this->travelTo(Carbon::parse('2026-11-10 20:00'));

    $this->admin = actingAsRole(Role::Admin);
    $this->doctorA = User::factory()->doctor()->create();
    $this->doctorB = User::factory()->doctor()->create();

    $bill = function (User $doctor, int $amount, string $paidOn, PaymentMethod $method = PaymentMethod::Cash) {
        $this->travelTo(Carbon::parse($paidOn));
        $invoice = app(SaveInvoice::class)->handle(null, ['patient_id' => Patient::factory()->create()->id, 'doctor_id' => $doctor->id], [['description' => 'كشف', 'quantity' => 1, 'unit_price' => $amount]], $this->admin);
        app(ManagePayments::class)->pay($invoice, $amount, $method, $this->admin);
        $this->travelTo(Carbon::parse('2026-11-10 20:00'));
    };

    $bill($this->doctorA, 300, '2026-11-02 18:00');
    $bill($this->doctorA, 150, '2026-11-09 18:00', PaymentMethod::InstaPay);
    $bill($this->doctorB, 400, '2026-11-09 19:00');
    $bill($this->doctorB, 999, '2026-10-20 19:00'); // previous month

    Expense::create(['expense_category_id' => ExpenseCategory::first()->id, 'amount' => 500, 'spent_at' => '2026-11-05', 'description' => 'إيجار']);

    foreach ([AppointmentStatus::Completed, AppointmentStatus::Completed, AppointmentStatus::Completed, AppointmentStatus::NoShow] as $i => $status) {
        Appointment::factory()->for($this->doctorA, 'doctor')->at('2026-11-0'.($i + 2).' 17:00')->status($status)->create();
    }
});

function november(?int $doctorId = null): ReportService
{
    return ReportService::for(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'), $doctorId);
}

it('sums collected revenue, expenses and net profit for the period', function () {
    $summary = november()->summary();

    expect($summary['revenue'])->toBe(850.0)
        ->and($summary['expenses'])->toBe(500.0)
        ->and($summary['net'])->toBe(350.0)
        ->and($summary['invoiced'])->toBe(850.0);
});

it('calculates the no-show rate from attended and missed appointments', function () {
    expect(november()->summary()['no_show_rate'])->toBe(25.0);
});

it('breaks revenue down by doctor, method and day', function () {
    $byDoctor = november()->revenueByDoctor()->keyBy('doctor');

    expect($byDoctor[$this->doctorA->displayName()]['revenue'])->toBe(450.0)
        ->and($byDoctor[$this->doctorB->displayName()]['revenue'])->toBe(400.0)
        ->and(november()->revenueByMethod()->all())->toBe(['cash' => 700.0, 'instapay' => 150.0])
        ->and(november()->revenueByDay()['2026-11-09'])->toBe(550.0)
        ->and(november()->revenueByDay())->toHaveCount(30);
});

it('totals revenue per service in pounds', function () {
    $services = november()->revenueByService()->keyBy('name');

    expect($services['كشف']['total'])->toBe(850.0)
        ->and($services['كشف']['count'])->toBe(3);
});

it('limits a doctor to their own figures', function () {
    expect(november($this->doctorA->id)->summary()['revenue'])->toBe(450.0)
        ->and(november($this->doctorA->id)->summary()['expenses'])->toBeNull()
        ->and(ReportService::scopedDoctorFor($this->doctorA, $this->doctorB->id))->toBe($this->doctorA->id)
        ->and(ReportService::scopedDoctorFor($this->admin, $this->doctorB->id))->toBe($this->doctorB->id);
});

it('ignores voided invoices and payments of deleted invoices', function () {
    $invoice = app(SaveInvoice::class)->handle(null, ['patient_id' => Patient::factory()->create()->id], [['description' => 'x', 'quantity' => 1, 'unit_price' => 1000]], $this->admin);
    app(ManagePayments::class)->void($invoice, 'خطأ', $this->admin);

    expect(november()->summary()['invoiced'])->toBe(850.0);
});

it('renders the reports page for each allowed role and hides money from doctors\' colleagues', function () {
    Livewire::test(Reports::class)->assertOk()->assertSee('850');

    actingAsRole(Role::Accountant);
    $this->get(route('reports.index'))->assertOk();

    $this->actingAs($this->doctorA);
    Livewire::test(Reports::class, ['doctorId' => $this->doctorB->id])
        ->assertSee($this->doctorA->displayName())
        ->assertDontSee($this->doctorB->displayName());

    actingAsRole(Role::Receptionist);
    $this->get(route('reports.index'))->assertForbidden();
});

it('exports Excel and PDF', function () {
    $query = ['from' => '2026-11-01', 'to' => '2026-11-30'];

    $excel = $this->get(route('reports.excel', ['sheet' => 'payments'] + $query));
    $excel->assertOk();
    expect($excel->headers->get('content-disposition'))->toContain('payments-2026-11-01-2026-11-30.xlsx');

    $this->get(route('reports.excel', ['sheet' => 'summary'] + $query))->assertOk();
    $this->get(route('reports.pdf', $query))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('reports.excel', ['sheet' => 'secrets'] + $query))->assertNotFound();

    $this->actingAs($this->doctorA)->get(route('reports.excel', ['sheet' => 'expenses'] + $query))->assertForbidden();
});

it('renders a role-appropriate dashboard for everyone', function (Role $role) {
    actingAsRole($role);

    Livewire::test(Dashboard::class)->assertOk();
    $this->get(route('dashboard'))->assertOk();
})->with([Role::Admin, Role::Doctor, Role::Receptionist, Role::Accountant]);

it('shows money on the admin dashboard but not on the doctor dashboard', function () {
    Livewire::test(Dashboard::class)->assertSee(__('Revenue this month'));

    $this->actingAs($this->doctorA);
    Livewire::test(Dashboard::class)->assertDontSee(__('Revenue this month'))->assertSee(__('Visits this month'));
});
