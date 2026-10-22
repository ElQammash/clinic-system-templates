<?php

namespace App\Actions\Patients;

use App\Enums\CustomFieldEntity;
use App\Models\Patient;
use App\Models\User;
use App\Services\CustomFields;
use App\Services\NumberSequence;
use Illuminate\Support\Facades\DB;

/**
 * Creates a patient (with the next file number) or updates an existing one.
 * Medical background fields are only written when the actor may see them.
 */
class SavePatient
{
    public function __construct(
        protected NumberSequence $sequence,
        protected CustomFields $customFields,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated attributes
     * @param  array<string, mixed>  $custom  custom field input
     */
    public function handle(?Patient $patient, array $data, array $custom, User $actor): Patient
    {
        if (! $actor->can('viewMedical', $patient ?? Patient::class)) {
            $data = array_diff_key($data, array_flip(Patient::MEDICAL_ATTRIBUTES));
        }

        return DB::transaction(function () use ($patient, $data, $custom, $actor) {
            $isNew = $patient === null;
            $patient ??= new Patient(['created_by' => $actor->id]);

            $patient->fill($data);
            $patient->custom_data = $this->customFields->clean(CustomFieldEntity::Patient, $custom, $patient->custom_data ?? []);

            if ($isNew) {
                $patient->file_number = $this->nextFileNumber();
            }

            $patient->save();

            return $patient;
        });
    }

    public function nextFileNumber(): string
    {
        $prefix = (string) settings('file_number_prefix', '');

        do {
            $number = $this->sequence->formatted('patient_file', $prefix, 5);
        } while (Patient::withTrashed()->where('file_number', $number)->exists()); // skip numbers taken by imported data

        return $number;
    }
}
