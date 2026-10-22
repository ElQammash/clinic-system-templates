<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to run again after an update. New permissions are added,
 * but role customisations made by the clinic owner are never overwritten
 * (only newly created roles receive their defaults; Admin always gets everything).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Role::cases() as $role) {
            $model = RoleModel::query()->where('name', $role->value)->where('guard_name', 'web')->first();
            $isNew = $model === null;
            $model ??= RoleModel::create(['name' => $role->value, 'guard_name' => 'web']);

            if ($isNew || $role === Role::Admin) {
                $model->syncPermissions(array_map(fn (Permission $p) => $p->value, $role->defaultPermissions()));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
