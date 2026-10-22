<?php

namespace App\Livewire\Patients;

use App\Actions\Patients\SavePatient;
use App\Enums\BloodType;
use App\Enums\CustomFieldEntity;
use App\Enums\CustomFieldType;
use App\Enums\Gender;
use App\Livewire\Forms\PatientForm;
use App\Models\Patient;
use App\Rules\PhoneNumber;
use App\Services\CustomFields;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Create / edit patient modal. Open it from anywhere with:
 *   $dispatch('create-patient')  or  $dispatch('edit-patient', { id: 5 })
 * After saving it dispatches `patient-saved` with the id.
 */
class PatientEditor extends Component
{
    public PatientForm $form;

    public bool $open = false;

    /** Go to the patient profile after creating (off when used inside booking flows). */
    public bool $redirectOnCreate = true;

    #[On('create-patient')]
    public function create(?string $name = null, ?string $phone = null): void
    {
        $this->authorize('create', Patient::class);

        $this->form->reset();
        $this->form->resetValidation();
        $this->form->name = (string) $name;
        $this->form->phone = $phone;
        $this->prepareCustomFields();
        $this->open = true;
    }

    /** Multiple-choice fields need an array to bind their checkboxes to. */
    protected function prepareCustomFields(): void
    {
        foreach (app(CustomFields::class)->for(CustomFieldEntity::Patient) as $field) {
            if ($field->type === CustomFieldType::MultiSelect && ! is_array($this->form->custom[$field->key] ?? null)) {
                $this->form->custom[$field->key] = [];
            }
        }
    }

    #[On('edit-patient')]
    public function edit(int $id): void
    {
        $patient = Patient::findOrFail($id);
        $this->authorize('update', $patient);

        $this->form->resetValidation();
        $this->form->setPatient($patient);
        $this->prepareCustomFields();
        $this->open = true;
    }

    /** Existing patients with the same phone, to avoid duplicate files. */
    #[Computed]
    public function duplicates(): Collection
    {
        $phone = PhoneNumber::normalize($this->form->phone);

        if ($this->form->patient || blank($phone) || strlen($phone) < 8) {
            return collect();
        }

        return Patient::query()->where('phone', $phone)->orWhere('phone_alt', $phone)->limit(5)->get();
    }

    public function save(SavePatient $action): void
    {
        $this->form->patient
            ? $this->authorize('update', $this->form->patient)
            : $this->authorize('create', Patient::class);

        [$data, $custom] = $this->form->payload();
        $isNew = $this->form->patient === null;

        $patient = $action->handle($this->form->patient, $data, $custom, auth()->user());

        $this->open = false;
        $this->dispatch('patient-saved', id: $patient->id);

        if ($isNew && $this->redirectOnCreate) {
            session()->flash('toast', __('Patient file :number created.', ['number' => $patient->file_number]));
            $this->redirectRoute('patients.show', $patient, navigate: true);

            return;
        }

        $this->dispatch('toast', message: __('Patient saved.'));
    }

    public function render()
    {
        return view('livewire.patients.patient-editor', [
            'genders' => Gender::options(),
            'bloodTypes' => BloodType::options(),
            'customFields' => app(CustomFields::class)->for(CustomFieldEntity::Patient),
            'canSeeMedical' => auth()->user()->can('viewMedical', $this->form->patient ?? Patient::class),
        ]);
    }
}
