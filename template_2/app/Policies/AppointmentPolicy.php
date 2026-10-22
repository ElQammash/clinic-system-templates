<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AppointmentsView->value);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can(Permission::AppointmentsView->value) && $this->ownsOrSeesAll($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::AppointmentsManage->value);
    }

    /** Book for a specific doctor: doctors without view_all may only fill their own schedule. */
    public function bookFor(User $user, User $doctor): bool
    {
        return $user->can(Permission::AppointmentsManage->value)
            && ($user->can(Permission::AppointmentsViewAll->value) || $user->is($doctor));
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can(Permission::AppointmentsManage->value) && $this->ownsOrSeesAll($user, $appointment);
    }

    /** Check-in, call in, complete, no-show. */
    public function changeStatus(User $user, Appointment $appointment): bool
    {
        return ($user->can(Permission::QueueManage->value) || $user->can(Permission::AppointmentsManage->value))
            && $this->ownsOrSeesAll($user, $appointment);
    }

    protected function ownsOrSeesAll(User $user, Appointment $appointment): bool
    {
        return $user->can(Permission::AppointmentsViewAll->value) || $appointment->doctor_id === $user->id;
    }
}
