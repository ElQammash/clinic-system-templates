<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Gate;

class PrescriptionPolicy
{
    /** Viewing / printing a prescription follows the visit it belongs to. */
    public function view(User $user, Prescription $prescription): bool
    {
        $visit = Visit::withTrashed()->find($prescription->visit_id);

        return $visit !== null && Gate::forUser($user)->allows('view', $visit);
    }
}
