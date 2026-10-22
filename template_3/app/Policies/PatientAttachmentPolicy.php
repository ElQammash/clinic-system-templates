<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;

class PatientAttachmentPolicy
{
    /** Opening / downloading a medical file. */
    public function view(User $user, PatientAttachment $attachment): bool
    {
        return $user->can(Permission::AttachmentsView->value)
            && $attachment->loadMissing('patient')->patient->isMedicallyVisibleTo($user);
    }

    public function create(User $user, ?Patient $patient = null): bool
    {
        return $user->can(Permission::AttachmentsUpload->value) && ! $patient?->trashed();
    }

    public function delete(User $user, PatientAttachment $attachment): bool
    {
        return $user->can(Permission::AttachmentsDelete->value);
    }
}
