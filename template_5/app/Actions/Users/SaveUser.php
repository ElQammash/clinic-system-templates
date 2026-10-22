<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a staff account, its role and (for doctors) the doctor profile.
 */
class SaveUser
{
    /**
     * @param  array{name: string, email: string, phone?: ?string, password?: ?string, locale?: ?string, is_active?: bool, role: string}  $data
     * @param  array<string, mixed>  $doctorProfile
     */
    public function handle(?User $user, array $data, array $doctorProfile, User $actor): User
    {
        $role = Role::from($data['role']);

        if ($user !== null) {
            $this->guardAgainstLockout($user, $role, (bool) ($data['is_active'] ?? true), $actor);
        }

        return DB::transaction(function () use ($user, $data, $role, $doctorProfile) {
            $user ??= new User;

            $user->fill([
                'name' => $data['name'],
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'locale' => $data['locale'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles([$role->value]);

            if ($role === Role::Doctor) {
                $user->doctorProfile()->updateOrCreate([], $doctorProfile);
            }

            return $user->load('roles', 'doctorProfile');
        });
    }

    public function toggleActive(User $user, User $actor): User
    {
        $this->guardAgainstLockout($user, $user->primaryRole() ?? Role::Receptionist, ! $user->is_active, $actor);

        $user->update(['is_active' => ! $user->is_active]);

        return $user;
    }

    /** The clinic must always keep at least one active admin, and you cannot lock yourself out. */
    protected function guardAgainstLockout(User $user, Role $newRole, bool $active, User $actor): void
    {
        $wasAdmin = $user->hasRole(Role::Admin->value);
        $staysAdmin = $newRole === Role::Admin && $active;

        if ($user->is($actor) && ! $active) {
            throw ValidationException::withMessages(['is_active' => __('You cannot deactivate your own account.')]);
        }

        if ($user->is($actor) && $wasAdmin && ! $staysAdmin) {
            throw ValidationException::withMessages(['role' => __('You cannot remove your own admin role.')]);
        }

        if ($wasAdmin && ! $staysAdmin) {
            $otherActiveAdmins = User::role(Role::Admin->value)->active()->whereKeyNot($user->id)->count();

            if ($otherActiveAdmins === 0) {
                throw ValidationException::withMessages(['role' => __('The clinic must have at least one active admin.')]);
            }
        }
    }
}
