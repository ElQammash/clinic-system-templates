<?php

namespace App\Livewire\Appointments;

use App\Actions\Appointments\BookAppointment;
use App\Enums\AppointmentSource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use App\Services\Scheduling\Availability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Booking / rescheduling modal. Open it from anywhere with:
 *   $dispatch('book-appointment', { patientId, doctorId, start })   — all optional
 *   $dispatch('reschedule-appointment', { id })
 */
class BookingForm extends Component
{
    public bool $open = false;

    public ?int $appointmentId = null;

    public ?int $patientId = null;

    public string $patientSearch = '';

    public ?int $doctorId = null;

    public ?int $visitTypeId = null;

    public string $date = '';

    public ?string $time = null;

    public string $source = 'booked';

    public ?string $notes = null;

    public bool $outsideHours = false;

    /** Waiting for the patient editor to create a new patient. */
    public bool $awaitingNewPatient = false;

    /** Visit this booking is the follow-up of (linked after saving). */
    public ?int $followUpFor = null;

    #[On('book-appointment')]
    public function openForBooking(?int $patientId = null, ?int $doctorId = null, ?string $start = null, ?string $source = null, ?string $date = null, ?int $followUpFor = null): void
    {
        $this->authorize('create', Appointment::class);

        $this->resetExcept();
        $this->resetValidation();
        $this->followUpFor = $followUpFor;

        $user = auth()->user();
        $this->patientId = $patientId;
        $this->doctorId = $doctorId ?: ($user->isDoctor() ? $user->id : $this->doctors->first()?->id);
        $this->visitTypeId = VisitType::query()->active()->value('id');
        $this->source = $source && AppointmentSource::tryFrom($source) ? $source : AppointmentSource::Booked->value;

        $start = $start ? CarbonImmutable::parse($start) : null;
        $this->date = ($start ?? ($date ? CarbonImmutable::parse($date) : CarbonImmutable::today()))->toDateString();
        $this->time = $start?->format('H:i');

        $this->open = true;
    }

    #[On('reschedule-appointment')]
    public function openForReschedule(int $id): void
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('update', $appointment);

        $this->resetExcept();
        $this->resetValidation();

        $this->appointmentId = $appointment->id;
        $this->patientId = $appointment->patient_id;
        $this->doctorId = $appointment->doctor_id;
        $this->visitTypeId = $appointment->visit_type_id;
        $this->date = $appointment->starts_at->toDateString();
        $this->time = $appointment->starts_at->format('H:i');
        $this->notes = $appointment->notes;
        $this->open = true;
    }

    #[On('patient-saved')]
    public function patientCreated(int $id): void
    {
        if ($this->awaitingNewPatient) {
            $this->patientId = $id;
            $this->awaitingNewPatient = false;
            $this->open = true;
        }
    }

    public function newPatient(): void
    {
        $this->awaitingNewPatient = true;
        $this->dispatch('create-patient', name: preg_match('/\d/', $this->patientSearch) ? null : $this->patientSearch,
            phone: preg_match('/^0\d+$/', $this->patientSearch) ? $this->patientSearch : null);
    }

    public function selectPatient(int $id): void
    {
        $this->patientId = $id;
        $this->patientSearch = '';
    }

    public function updatedDoctorId(): void
    {
        $this->time = null;
    }

    public function updatedDate(): void
    {
        $this->time = null;
    }

    public function updatedVisitTypeId(): void
    {
        $this->time = null;
    }

    #[Computed]
    public function doctors(): Collection
    {
        $user = auth()->user();

        return User::query()->doctors()->active()->with('doctorProfile')
            ->when(! $user->can('appointments.view_all'), fn ($q) => $q->whereKey($user->id))
            ->orderBy('name')->get();
    }

    #[Computed]
    public function patient(): ?Patient
    {
        return $this->patientId ? Patient::find($this->patientId) : null;
    }

    #[Computed]
    public function patientResults(): Collection
    {
        if (mb_strlen(trim($this->patientSearch)) < 2) {
            return collect();
        }

        return Patient::query()->search($this->patientSearch)->limit(8)->get();
    }

    /** @return list<CarbonImmutable> */
    #[Computed]
    public function slots(): array
    {
        $doctor = $this->doctors->firstWhere('id', $this->doctorId);

        if (! $doctor || ! $this->date) {
            return [];
        }

        try {
            $date = CarbonImmutable::parse($this->date);
        } catch (\Throwable) {
            return [];
        }

        return app(Availability::class)->freeSlots($doctor, $date, $this->duration($doctor), $this->appointmentId);
    }

    protected function duration(User $doctor): int
    {
        $type = $this->visitTypeId ? VisitType::find($this->visitTypeId) : null;

        return $type?->durationFor($doctor) ?? ($doctor->doctorProfile?->slot_minutes ?? 15);
    }

    public function save(BookAppointment $action): void
    {
        $appointment = $this->appointmentId ? Appointment::findOrFail($this->appointmentId) : null;
        $appointment ? $this->authorize('update', $appointment) : $this->authorize('create', Appointment::class);

        $this->validate([
            'patientId' => ['required', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctorId' => ['required', 'integer'],
            'visitTypeId' => ['nullable', Rule::exists('visit_types', 'id')],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'source' => ['required', Rule::enum(AppointmentSource::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], attributes: [
            'patientId' => __('Patient'),
            'doctorId' => __('Doctor'),
            'time' => __('Time'),
        ]);

        $doctor = User::findOrFail($this->doctorId);
        $this->authorize('bookFor', [Appointment::class, $doctor]);

        $result = $action->handle([
            'patient_id' => $this->patientId,
            'doctor_id' => $this->doctorId,
            'visit_type_id' => $this->visitTypeId,
            'starts_at' => "{$this->date} {$this->time}",
            'source' => $this->source,
            'notes' => $this->notes,
            'outside_hours' => $this->outsideHours,
        ], auth()->user(), $appointment);

        if ($this->followUpFor && ($visit = Visit::find($this->followUpFor)) && auth()->user()->can('update', $visit)) {
            $visit->update(['follow_up_appointment_id' => $result->id, 'follow_up_date' => $result->starts_at->toDateString()]);
        }

        $this->open = false;
        $this->dispatch('appointment-saved', id: $result->id);
        $this->dispatch('toast', message: $appointment ? __('Appointment rescheduled.') : __('Appointment booked.'));
    }

    public function render()
    {
        return view('livewire.appointments.booking-form', [
            'visitTypes' => VisitType::query()->active()->get(),
            'sources' => AppointmentSource::bookableOptions(),
            'canOverrideHours' => auth()->user()->can('schedule.manage'),
        ]);
    }
}
