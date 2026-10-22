<?php

namespace App\Livewire\Patients;

use App\Enums\CustomFieldEntity;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\CustomFields;
use App\Services\PatientTimeline;
use App\Support\Format;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Show extends Component
{
    public Patient $patient;

    #[Url]
    public string $tab = 'overview';

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient;

        $this->logMedicalAccess();
    }

    /** Record who opened a medical file (at most once per 10 minutes per user & patient). */
    protected function logMedicalAccess(): void
    {
        $user = auth()->user();

        if (! $user->can('viewMedical', $this->patient)) {
            return;
        }

        $key = "viewed-patient-{$this->patient->id}";
        $last = session($key);

        if ($last === null || now()->diffInMinutes($last) >= 10) {
            activity('medical')->causedBy($user)->performedOn($this->patient)->event('viewed')->log('medical record viewed');
            session([$key => now()]);
        }
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->patient);

        $this->patient->delete();

        session()->flash('toast', __('Patient file deleted. It can be restored from the deleted patients list.'));
        $this->redirectRoute('patients.index', navigate: true);
    }

    #[On('patient-saved')]
    #[On('attachment-saved')]
    #[On('appointment-saved')]
    #[On('appointment-updated')]
    public function refreshPatient(): void
    {
        $this->patient->refresh();
    }

    /** Tabs shown on the profile. Later modules add their own (visits, invoices…). */
    protected function tabs(bool $canSeeMedical): array
    {
        return array_filter([
            'overview' => __('Overview'),
            'timeline' => $canSeeMedical ? __('Timeline') : null,
            'visits' => $canSeeMedical ? __('Visits') : null,
            'appointments' => auth()->user()->can('viewAny', Appointment::class) ? __('Appointments') : null,
            'billing' => auth()->user()->can('viewAny', Invoice::class) ? __('Billing') : null,
            'attachments' => __('Attachments'),
        ]);
    }

    public function render()
    {
        $user = auth()->user();
        $canSeeMedical = $user->can('viewMedical', $this->patient);
        $tabs = $this->tabs($canSeeMedical);

        if (! array_key_exists($this->tab, $tabs)) {
            $this->tab = 'overview';
        }

        return view('livewire.patients.show', [
            'canSeeMedical' => $canSeeMedical,
            'tabs' => $tabs,
            'customValues' => app(CustomFields::class)->display(CustomFieldEntity::Patient, $this->patient->custom_data),
            'timeline' => $this->tab === 'timeline' ? app(PatientTimeline::class)->for($this->patient, $user) : collect(),
            'visits' => $this->tab === 'visits'
                ? $this->patient->visits()->with('doctor', 'prescription.items')->latest('visited_at')->get()
                : collect(),
            'invoices' => $this->tab === 'billing'
                ? $this->patient->invoices()->visibleTo($user)->latest('issued_at')->get()
                : collect(),
            'balance' => $user->can('viewAny', Invoice::class) ? $this->patient->balance() : 0,
            'canStartVisit' => $user->isDoctor() && $user->can('create', Visit::class) && ! $this->patient->trashed(),
            'appointments' => $this->tab === 'appointments'
                ? $this->patient->appointments()->with('doctor', 'visitType')->visibleTo($user)->latest('starts_at')->get()
                : collect(),
            'whatsappUrl' => $this->patient->phone ? 'https://wa.me/'.Format::internationalPhone($this->patient->phone) : null,
        ])->title($this->patient->name);
    }
}
