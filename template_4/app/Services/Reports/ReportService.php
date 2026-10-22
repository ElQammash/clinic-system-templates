<?php

namespace App\Services\Reports;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * All dashboard / report numbers in one place, database-agnostic (MySQL & SQLite).
 * Money is returned in pounds. "Revenue" means money actually collected (payments).
 *
 * Scope: when $doctorId is set, every figure is limited to that doctor
 * (used for "own reports" and the doctor filter).
 */
class ReportService
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?int $doctorId = null,
    ) {
        $this->from = $from->startOfDay();
        $this->to = $to->endOfDay();
    }

    public static function for(CarbonImmutable $from, CarbonImmutable $to, ?int $doctorId = null): self
    {
        return new self($from, $to, $doctorId);
    }

    /** Doctors limited to their own figures; everyone else may pick any doctor (or all). */
    public static function scopedDoctorFor(User $user, ?int $requested): ?int
    {
        if ($user->can('reports.view_all') || $user->can('reports.view_financial')) {
            return $requested ?: null;
        }

        return $user->id;
    }

    // ---------------------------------------------------------------- KPIs

    /** @return array<string, int|float|null> */
    public function summary(): array
    {
        $appointments = $this->appointments()->get(['status']);
        $total = $appointments->count();
        $noShows = $appointments->where('status', AppointmentStatus::NoShow)->count();
        $attended = $appointments->where('status', AppointmentStatus::Completed)->count();

        $revenue = $this->revenue();
        $expenses = $this->doctorId ? null : $this->expensesTotal();

        return [
            'appointments' => $total,
            'completed' => $attended,
            'cancelled' => $appointments->where('status', AppointmentStatus::Cancelled)->count(),
            'no_shows' => $noShows,
            // Rate among appointments that should have happened (attended + no-show).
            'no_show_rate' => ($attended + $noShows) > 0 ? round($noShows / ($attended + $noShows) * 100, 1) : 0.0,
            'new_patients' => $this->newPatients(),
            'visits' => $this->visits()->count(),
            'revenue' => $revenue,
            'invoiced' => $this->minor($this->invoices()->sum('total')),
            'discounts' => $this->minor($this->invoices()->sum('discount_amount')),
            'outstanding' => $this->minor($this->invoices()->sum('balance')),
            'expenses' => $expenses,
            'net' => $expenses === null ? null : round($revenue - $expenses, 2),
        ];
    }

    public function revenue(): float
    {
        return $this->minor($this->payments()->sum('payments.amount'));
    }

    public function expensesTotal(): float
    {
        return $this->minor(Expense::query()->whereBetween('spent_at', [$this->from->toDateString(), $this->to->toDateString()])->sum('amount'));
    }

    public function newPatients(): int
    {
        $query = Patient::query()->whereBetween('created_at', [$this->from, $this->to]);

        if ($this->doctorId) {
            // "New for this doctor": first appointment with the doctor falls in the range.
            $query = Patient::query()->whereHas('appointments', fn (Builder $q) => $q->where('doctor_id', $this->doctorId))
                ->whereDoesntHave('appointments', fn (Builder $q) => $q->where('doctor_id', $this->doctorId)->where('starts_at', '<', $this->from))
                ->whereHas('appointments', fn (Builder $q) => $q->where('doctor_id', $this->doctorId)->whereBetween('starts_at', [$this->from, $this->to]));
        }

        return $query->count();
    }

    // ------------------------------------------------------------ Breakdowns

    /**
     * Collected money per day (every day in the range, zero-filled).
     *
     * @return Collection<string, float> 'Y-m-d' => pounds
     */
    public function revenueByDay(): Collection
    {
        $rows = $this->payments()
            ->selectRaw('DATE(payments.paid_at) as day, SUM(payments.amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = collect();
        for ($day = $this->from; $day->lessThanOrEqualTo($this->to); $day = $day->addDay()) {
            $series[$day->toDateString()] = $this->minor($rows[$day->toDateString()] ?? 0);
        }

        return $series;
    }

    /** @return Collection<int, array{doctor: string, revenue: float, visits: int, color: string}> */
    public function revenueByDoctor(): Collection
    {
        $revenue = $this->payments()
            ->selectRaw('invoices.doctor_id, SUM(payments.amount) as total')
            ->groupBy('invoices.doctor_id')
            ->pluck('total', 'doctor_id');

        $visits = $this->visits()->selectRaw('doctor_id, COUNT(*) as total')->groupBy('doctor_id')->pluck('total', 'doctor_id');

        return User::query()->doctors()->with('doctorProfile')->get()
            ->when($this->doctorId, fn ($c) => $c->where('id', $this->doctorId))
            ->map(fn (User $doctor) => [
                'doctor' => $doctor->displayName(),
                'revenue' => $this->minor($revenue[$doctor->id] ?? 0),
                'visits' => (int) ($visits[$doctor->id] ?? 0),
                'color' => $doctor->doctorProfile?->color ?? '#0d9488',
            ])
            ->sortByDesc('revenue')
            ->values();
    }

    /** Invoiced amount per service / item description. */
    public function revenueByService(int $limit = 10): Collection
    {
        return InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.status', '!=', InvoiceStatus::Void->value)
            ->whereBetween('invoices.issued_at', [$this->from, $this->to])
            ->when($this->doctorId, fn ($q) => $q->where('invoices.doctor_id', $this->doctorId))
            // "line_sum", not "total": InvoiceItem casts `total` as Money, which would divide by 100 twice.
            ->selectRaw('invoice_items.description as name, SUM(invoice_items.quantity) as count, SUM(invoice_items.total) as line_sum')
            ->groupBy('invoice_items.description')
            ->orderByDesc('line_sum')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count, 'total' => $this->minor($row->line_sum)]);
    }

    /** @return Collection<string, float> method => pounds */
    public function revenueByMethod(): Collection
    {
        return $this->payments()
            ->selectRaw('payments.method, SUM(payments.amount) as total')
            ->groupBy('payments.method')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->method->value => $this->minor($row->total)]);
    }

    public function expensesByCategory(): Collection
    {
        return Expense::query()
            ->whereBetween('spent_at', [$this->from->toDateString(), $this->to->toDateString()])
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(fn ($row) => ['name' => $row->category?->name() ?? '—', 'total' => $this->minor($row->total)])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Income vs expenses per month in the range.
     *
     * @return Collection<string, array{income: float, expenses: float}>
     */
    public function incomeVsExpensesByMonth(): Collection
    {
        $months = collect();
        for ($m = $this->from->startOfMonth(); $m->lessThanOrEqualTo($this->to); $m = $m->addMonth()) {
            $start = $m->max($this->from);
            $end = $m->endOfMonth()->min($this->to);
            $slice = new self($start, $end, $this->doctorId);
            $months[$m->format('Y-m')] = ['income' => $slice->revenue(), 'expenses' => $slice->expensesTotal()];
        }

        return $months;
    }

    /** @return Collection<string, int> status => count */
    public function appointmentsByStatus(): Collection
    {
        return $this->appointments()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->total]);
    }

    /** No-show rate per doctor. */
    public function noShowsByDoctor(): Collection
    {
        $rows = $this->appointments()
            ->selectRaw('doctor_id, status, COUNT(*) as total')
            ->whereIn('status', [AppointmentStatus::Completed->value, AppointmentStatus::NoShow->value])
            ->groupBy('doctor_id', 'status')
            ->get()
            ->groupBy('doctor_id');

        return User::query()->doctors()->get()
            ->when($this->doctorId, fn ($c) => $c->where('id', $this->doctorId))
            ->map(function (User $doctor) use ($rows) {
                $mine = $rows->get($doctor->id, collect());
                $done = (int) $mine->firstWhere('status', AppointmentStatus::Completed)?->total;
                $missed = (int) $mine->firstWhere('status', AppointmentStatus::NoShow)?->total;

                return [
                    'doctor' => $doctor->displayName(),
                    'completed' => $done,
                    'no_shows' => $missed,
                    'rate' => ($done + $missed) > 0 ? round($missed / ($done + $missed) * 100, 1) : 0.0,
                ];
            })
            ->values();
    }

    // -------------------------------------------------------------- Queries

    protected function appointments(): Builder
    {
        return Appointment::query()
            ->whereBetween('starts_at', [$this->from, $this->to])
            ->when($this->doctorId, fn ($q) => $q->where('doctor_id', $this->doctorId));
    }

    protected function visits(): Builder
    {
        return Visit::query()
            ->whereBetween('visited_at', [$this->from, $this->to])
            ->when($this->doctorId, fn ($q) => $q->where('doctor_id', $this->doctorId));
    }

    protected function invoices(): Builder
    {
        return Invoice::query()
            ->notVoid()
            ->whereBetween('issued_at', [$this->from, $this->to])
            ->when($this->doctorId, fn ($q) => $q->where('doctor_id', $this->doctorId));
    }

    /** Payments in the range (joined to invoices for the doctor filter; deleted invoices excluded). */
    protected function payments(): Builder
    {
        return Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereBetween('payments.paid_at', [$this->from, $this->to])
            ->when($this->doctorId, fn ($q) => $q->where('invoices.doctor_id', $this->doctorId));
    }

    protected function minor(int|float|string|null $value): float
    {
        return round(((float) $value) / 100, 2);
    }
}
