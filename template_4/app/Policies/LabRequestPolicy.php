<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\User;

class LabRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::LabView->value);
    }

    public function view(User $user, LabRequest $request): bool
    {
        return $user->can(Permission::LabView->value)
            && ($request->doctor_id === $user->id || Patient::withTrashed()->find($request->patient_id)?->isMedicallyVisibleTo($user));
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::LabManage->value);
    }

    public function update(User $user, LabRequest $request): bool
    {
        return $user->can(Permission::LabManage->value) && $this->view($user, $request);
    }
}
