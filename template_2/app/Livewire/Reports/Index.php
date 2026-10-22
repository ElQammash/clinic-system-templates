<?php

namespace App\Livewire\Reports;

use App\Enums\AppointmentStatus;
use App\Enums\Module;
use App\Enums\PaymentMethod;
use App\Models\User;
use App\Services\Reports\ReportService;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Reports')]
class Index extends Component
{
    #[Url]
    public string $period = 'this_month';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(as: 'doctor', except: 0)]
    public int $doctorId = 0;

    public function mount(): void
    {
        abort_unless(self::canView(auth()->user()), 403);
        $this->applyPeriod();
    }

    public static function canView(User $user): bool
    {
        return $user->can('reports.view_all') || $user->can('reports.view_financial') || $user->can('reports.view_own');
    }

    public function updatedPeriod(): void
    {
        $this->applyPeriod();
    }

    public function updatedFrom(): void
    {
        $this->period = 'custom';
    }

    public function updatedTo(): void
    {
        $this->period = 'custom';
    }

    protected function applyPeriod(): void
    {
        $today = CarbonImmutable::today();

        [$from, $to] = match ($this->period) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'this_week' => [$today->startOfWeek(CarbonImmutable::SATURDAY), $today],
            'last_30' => [$today->subDays(29), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->startOfYear(), $today],
            'custom' => [$this->parse($this->from, $today->startOfMonth()), $this->parse($this->to, $today)],
            default => [$today->startOfMonth(), $today],
        };

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    protected function parse(string $value, CarbonImmutable $fallback): CarbonImmutable
    {
        try {
            return $value ? CarbonImmutable::parse($value) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function render()
    {
        $user = auth()->user();
        $this->applyPeriod();

        $report = ReportService::for(CarbonImmutable::parse($this->from), CarbonImmutable::parse($this->to), ReportService::scopedDoctorFor($user, $this->doctorId));
        $financial = $user->can('reports.view_all') || $user->can('reports.view_financial');

        return view('livewire.reports.index', [
            'summary' => $report->summary(),
            'byDay' => $report->revenueByDay(),
            'byDoctor' => $report->revenueByDoctor(),
            'byService' => $report->revenueByService(),
            'byMethod' => $report->revenueByMethod(),
            'byStatus' => $report->appointmentsByStatus(),
            'noShows' => $report->noShowsByDoctor(),
            'expensesByCategory' => $financial && ! $report->doctorId && Module::Expenses->enabled() ? $report->expensesByCategory() : collect(),
            'incomeVsExpenses' => $financial && ! $report->doctorId ? $report->incomeVsExpensesByMonth() : collect(),
            'financial' => $financial,
            'canPickDoctor' => $financial || $user->can('reports.view_all'),
            'doctors' => User::query()->doctors()->orderBy('name')->get(),
            'methods' => PaymentMethod::options(),
            'statuses' => collect(AppointmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ['label' => $s->label(), 'color' => $s->color()]]),
            'exportQuery' => ['from' => $this->from, 'to' => $this->to, 'doctor' => $report->doctorId],
        ]);
    }
}
