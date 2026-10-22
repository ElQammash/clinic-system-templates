<?php

namespace App\Actions\Users;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

class SyncRolePermissions
{
    /** @param  list<string>  $permissions */
    public function handle(Role $role, array $permissions): void
    {
        if ($role === Role::Admin) {
            throw ValidationException::withMessages(['role' => __('The admin role always has every permission.')]);
        }

        $valid = array_column(Permission::cases(), 'value');
        $permissions = array_values(array_intersect($permissions, $valid));

        $model = RoleModel::findByName($role->value, 'web');
        $before = $model->permissions->pluck('name')->all();

        $model->syncPermissions($permissions);

        activity('roles')
            ->performedOn($model)
            ->event('updated')
            ->withProperties([
                'added' => array_values(array_diff($permissions, $before)),
                'removed' => array_values(array_diff($before, $permissions)),
            ])
            ->log('role permissions updated');
    }
}
