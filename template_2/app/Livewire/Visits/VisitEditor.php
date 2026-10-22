<?php

namespace App\Livewire\Visits;

use App\Actions\Visits\SaveVisit;
use App\Enums\CustomFieldEntity;
use App\Enums\CustomFieldType;
use App\Enums\Module;
use App\Livewire\Forms\VisitForm;
use App\Models\Visit;
use App\Services\CustomFields;
use Livewire\Attributes\On;
use Livewire\Component;

class VisitEditor extends Component
{
    public Visit $visit;

    public VisitForm $form;

    public bool $awaitingFollowUp = false;

    public function mount(Visit $visit): void
    {
        $this->authorize('view', $visit);
        $this->visit = $visit;
        $this->form->setVisit($visit);

        foreach (app(CustomFields::class)->for(CustomFieldEntity::Visit) as $field) {
            if ($field->type === CustomFieldType::MultiSelect && ! is_array($this->form->custom[$field->key] ?? null)) {
                $this->form->custom[$field->key] = [];
            }
        }

        activity('medical')->causedBy(auth()->user())->performedOn($visit)->event('viewed')->log('visit viewed');
    }

    public function save(SaveVisit $action): void
    {
        $this->authorize('update', $this->visit);

        [$data, $custom] = $this->form->payload();
        $action->handle($this->visit, $data, $custom);

        $this->dispatch('visit-saved');
        $this->dispatch('toast', message: __('Visit saved.'));
    }

    /** Save and finish: completes the appointment so the patient leaves the waiting room. */
    public function finish(SaveVisit $action): void
    {
        $this->save($action);
        $action->complete($this->visit, auth()->user());

        session()->flash('toast', __('Visit completed.'));
        $this->redirectRoute(auth()->user()->can('queue.manage') ? 'queue' : 'visits.index', navigate: true);
    }

    public function bookFollowUp(SaveVisit $action): void
    {
        $this->save($action);
        $this->awaitingFollowUp = true;

        $this->dispatch('book-appointment',
            patientId: $this->visit->patient_id,
            doctorId: $this->visit->doctor_id,
            date: $this->visit->follow_up_date?->toDateString(),
            source: 'follow_up',
            followUpFor: $this->visit->id,
        );
    }

    #[On('appointment-saved')]
    public function followUpBooked(): void
    {
        $this->awaitingFollowUp = false;
        $this->visit->refresh();
    }

    public function render()
    {
        $patient = $this->visit->loadMissing('patient', 'doctor')->patient;
        $user = auth()->user();

        return view('livewire.visits.visit-editor', [
            'patient' => $patient,
            'canEdit' => $user->can('update', $this->visit),
            'canPrescribe' => $user->can('prescribe', $this->visit),
            'labEnabled' => Module::Lab->enabled() && $user->can('lab.view'),
            'customFields' => app(CustomFields::class)->for(CustomFieldEntity::Visit),
            'bmi' => $this->form->bmi(),
            'previousVisits' => $patient->visits()->whereKeyNot($this->visit->id)->with('doctor')->latest('visited_at')->limit(5)->get(),
            'lastVitals' => $patient->visits()->whereKeyNot($this->visit->id)->whereNotNull('weight')->latest('visited_at')->first(),
            'followUp' => $this->visit->followUpAppointment()->first(),
        ])->title(__('Visit').' · '.$patient->name);
    }
}
