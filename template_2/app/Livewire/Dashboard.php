<?php

namespace App\Livewire;

use App\Enums\AppointmentStatus;
use App\Enums\Module;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkingHour;
use App\Services\Reports\ReportService;
use App\Support\Branding;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Role-aware home page: today's work for everyone, money for those allowed to see it.
 */
#[Title('Dashboard')]
class Dashboard extends Component
{
    /**
     * "Getting started" steps shown to the admin until the clinic is fully set up.
     *
     * @return list<array{label: string, done: bool, url: ?string}>
     */
    protected function setupChecklist(): array
    {
        $settingsUrl = fn (string $tab) => Route::has('settings.clinic') ? route('settings.clinic', ['tab' => $tab]) : null;

        return [
            ['label' => __('Add clinic phone and address'), 'done' => filled(settings('phone')) && filled(settings('address')), 'url' => $settingsUrl('general')],
            ['label' => __('Upload your logo'), 'done' => Branding::hasLogo(), 'url' => $settingsUrl('branding')],
            ['label' => __('Add at least one doctor'), 'done' => User::role(Role::Doctor->value)->exists(), 'url' => route('settings.users')],
            ['label' => __('Add reception staff'), 'done' => User::role(Role::Receptionist->value)->exists(), 'url' => route('settings.users')],
            ['label' => __('Review working hours'), 'done' => WorkingHour::query()->exists(), 'url' => route('settings.schedule')],
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $today = CarbonImmutable::today();
        $doctorScope = $user->isDoctor() && ! $user->can('reports.view_all') ? $user->id : null;
        $seesMoney = $user->can('reports.view_all') || $user->can('reports.view_financial') || $user->can('invoices.create');
        $seesAppointments = $user->can('appointments.view');

        $todayReport = ReportService::for($today, $today, $doctorScope);
        $monthReport = ReportService::for($today->startOfMonth(), $today, $doctorScope);
        $last30 = ReportService::for($today->subDays(29), $today, $doctorScope);

        $todayAppointments = $seesAppointments
            ? Appointment::query()->with('patient', 'doctor', 'visitType')->onDate($today)->visibleTo($user)->orderBy('starts_at')->get()
            : collect();

        $checklist = $user->can('settings.manage') ? $this->setupChecklist() : [];

        return view('livewire.dashboard', [
            'user' => $user,
            'seesMoney' => $seesMoney,
            'seesAppointments' => $seesAppointments,
            'isFinancial' => $user->can('reports.view_all') || $user->can('reports.view_financial'),
            'today' => $todayReport->summary(),
            'month' => $monthReport->summary(),
            'revenueChart' => $seesMoney ? $last30->revenueByDay() : collect(),
            'todayAppointments' => $todayAppointments,
            'counts' => [
                'waiting' => $todayAppointments->where('status', AppointmentStatus::Arrived)->count(),
                'with_doctor' => $todayAppointments->where('status', AppointmentStatus::InProgress)->count(),
                'remaining' => $todayAppointments->whereIn('status', [AppointmentStatus::Booked, AppointmentStatus::Confirmed])->count(),
                'done' => $todayAppointments->where('status', AppointmentStatus::Completed)->count(),
            ],
            'outstanding' => $seesMoney ? ((int) Invoice::query()->withBalance()->sum('balance')) / 100 : 0,
            'lowStock' => Module::Inventory->enabled() && $user->can('inventory.view') ? InventoryItem::query()->lowStock()->get() : collect(),
            'byDoctor' => $user->can('reports.view_all') ? $monthReport->revenueByDoctor() : collect(),
            'checklist' => collect($checklist)->contains('done', false) ? $checklist : [],
        ]);
    }
}
