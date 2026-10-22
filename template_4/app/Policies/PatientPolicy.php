<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::PatientsView->value);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsView->value);
    }

    /**
     * Medical background (blood type, allergies, chronic diseases) and the medical timeline.
     * Requires medical access and, for doctors, that the patient is theirs or records are shared.
     */
    public function viewMedical(User $user, ?Patient $patient = null): bool
    {
        if (! $user->can(Permission::VisitsView->value)) {
            return false;
        }

        return $patient === null || ! $patient->exists || $patient->isMedicallyVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PatientsCreate->value);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsUpdate->value) && ! $patient->trashed();
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsDelete->value);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsDelete->value);
    }

    /** Patients are never hard-deleted: medical and financial records must be kept. */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }
}
