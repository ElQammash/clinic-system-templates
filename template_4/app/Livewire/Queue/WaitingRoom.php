<?php

namespace App\Livewire\Queue;

use App\Actions\Appointments\ChangeAppointmentStatus;
use App\Actions\Visits\StartVisit;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use App\Services\Scheduling\Queue;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Waiting room')]
class WaitingRoom extends Component
{
    #[Url(as: 'doctor')]
    public int $doctorId = 0;

    // Walk-in form
    public bool $showWalkIn = false;

    public ?int $walkInPatientId = null;

    public string $walkInSearch = '';

    public ?int $walkInDoctorId = null;

    public ?int $walkInVisitTypeId = null;

    public bool $awaitingNewPatient = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->can('queue.manage') || $user->can('appointments.view'), 403);

        if (! $user->can('appointments.view_all')) {
            $this->doctorId = $user->id;
        }
    }

    #[Computed]
    public function doctors(): Collection
    {
        $user = auth()->user();

        return User::query()->doctors()->active()->with('doctorProfile')
            ->when(! $user->can('appointments.view_all'), fn ($q) => $q->whereKey($user->id))
            ->orderBy('name')->get();
    }

    public function changeStatus(int $id, string $status, ChangeAppointmentStatus $action): void
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('changeStatus', $appointment);

        $action->handle($appointment, AppointmentStatus::from($status), auth()->user());
        $this->dispatch('toast', message: __('Appointment updated: :status', ['status' => AppointmentStatus::from($status)->label()]));
    }

    /** Call the next waiting patient of a doctor into the room. */
    public function callNext(int $doctorId, Queue $queue, ChangeAppointmentStatus $action): void
    {
        $next = $queue->next($doctorId);

        if (! $next) {
            $this->dispatch('toast', message: __('No patients are waiting.'), type: 'error');

            return;
        }

        $this->authorize('changeStatus', $next);
        $user = auth()->user();

        // A doctor calling their own next patient goes straight to the visit screen.
        if ($next->doctor_id === $user->id && $user->can('create', Visit::class)) {
            $visit = app(StartVisit::class)->handle($next->patient, $user, $next);
            $this->redirectRoute('visits.show', $visit, navigate: true);

            return;
        }

        $action->handle($next, AppointmentStatus::InProgress, $user);

        $this->dispatch('toast', message: __('Calling :name (No. :number)', ['name' => $next->patient->name, 'number' => $next->queue_number]));
    }

    public function openWalkIn(): void
    {
        $this->authorize('create', Appointment::class);
        $this->reset('walkInPatientId', 'walkInSearch', 'walkInVisitTypeId');
        $this->walkInDoctorId = $this->doctorId ?: $this->doctors->first()?->id;
        $this->walkInVisitTypeId = VisitType::query()->active()->value('id');
        $this->resetValidation();
        $this->showWalkIn = true;
    }

    #[Computed]
    public function walkInResults(): Collection
    {
        return mb_strlen(trim($this->walkInSearch)) >= 2
            ? Patient::query()->search($this->walkInSearch)->limit(6)->get()
            : collect();
    }

    public function newWalkInPatient(): void
    {
        $this->awaitingNewPatient = true;
        $this->dispatch('create-patient', name: preg_match('/\d/', $this->walkInSearch) ? null : $this->walkInSearch);
    }

    #[On('patient-saved')]
    public function patientCreated(int $id): void
    {
        if ($this->awaitingNewPatient) {
            $this->walkInPatientId = $id;
            $this->awaitingNewPatient = false;
            $this->showWalkIn = true;
        }
    }

    public function registerWalkIn(Queue $queue): void
    {
        $this->authorize('create', Appointment::class);

        $this->validate([
            'walkInPatientId' => ['required', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'walkInDoctorId' => ['required', 'integer'],
            'walkInVisitTypeId' => ['nullable', Rule::exists('visit_types', 'id')],
        ], attributes: ['walkInPatientId' => __('Patient'), 'walkInDoctorId' => __('Doctor')]);

        $doctor = User::findOrFail($this->walkInDoctorId);
        $this->authorize('bookFor', [Appointment::class, $doctor]);

        $appointment = $queue->walkIn(
            Patient::findOrFail($this->walkInPatientId),
            $doctor,
            $this->walkInVisitTypeId ? VisitType::find($this->walkInVisitTypeId) : null,
            auth()->user(),
        );

        $this->showWalkIn = false;
        $this->dispatch('toast', message: __('Added to the queue as No. :number.', ['number' => $appointment->queue_number]));
    }

    public function render(Queue $queue)
    {
        return view('livewire.queue.waiting-room', [
            'groups' => $queue->today(auth()->user(), $this->doctorId ?: null),
            'visitTypes' => VisitType::query()->active()->get(),
            'walkInPatient' => $this->walkInPatientId ? Patient::find($this->walkInPatientId) : null,
            'canManage' => auth()->user()->can('queue.manage') || auth()->user()->can('appointments.manage'),
            'canInvoice' => auth()->user()->can('invoices.create'),
            // Doctors open the visit of their own patients (owners with full access can open any).
            'canStartVisit' => fn (Appointment $a) => auth()->user()->can('create', Visit::class)
                && ($a->doctor_id === auth()->id() || auth()->user()->can('records.view_all')),
        ]);
    }
}
