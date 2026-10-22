<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;

/**
 * Medical records. Receptionists and accountants have no visits.* permissions,
 * so they can never open visits, notes, prescriptions or print them.
 */
class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VisitsView->value);
    }

    public function view(User $user, Visit $visit): bool
    {
        if (! $user->can(Permission::VisitsView->value)) {
            return false;
        }

        return $visit->doctor_id === $user->id
            || Patient::withTrashed()->find($visit->patient_id)?->isMedicallyVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::VisitsCreate->value);
    }

    /** Doctors edit their own visits; users with records.view_all (e.g. the owner) can edit any. */
    public function update(User $user, Visit $visit): bool
    {
        return $user->can(Permission::VisitsUpdate->value)
            && ! $visit->trashed()
            && ($visit->doctor_id === $user->id || $user->can(Permission::RecordsViewAll->value));
    }

    public function prescribe(User $user, Visit $visit): bool
    {
        return $user->can(Permission::PrescriptionsManage->value) && $this->update($user, $visit);
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->can(Permission::VisitsDelete->value);
    }
}
