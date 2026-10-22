<?php

namespace App\Livewire\Settings;

use App\Actions\Users\SyncRolePermissions;
use App\Enums\Permission;
use App\Enums\Role;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

#[Title('Roles & permissions')]
class Roles extends Component
{
    /** @var array<string, list<string>> role => permission names */
    public array $matrix = [];

    public function mount(): void
    {
        $this->authorize(Permission::RolesManage->value);
        $this->loadMatrix();
    }

    public function save(SyncRolePermissions $action): void
    {
        $this->authorize(Permission::RolesManage->value);

        foreach (Role::cases() as $role) {
            if ($role !== Role::Admin) {
                $action->handle($role, $this->matrix[$role->value] ?? []);
            }
        }

        $this->loadMatrix();
        $this->dispatch('toast', message: __('Permissions saved.'));
    }

    public function resetToDefaults(string $role): void
    {
        $this->authorize(Permission::RolesManage->value);

        $role = Role::from($role);
        $this->matrix[$role->value] = array_map(fn (Permission $p) => $p->value, $role->defaultPermissions());
    }

    protected function loadMatrix(): void
    {
        $this->matrix = RoleModel::with('permissions')->get()
            ->mapWithKeys(fn (RoleModel $role) => [$role->name => $role->permissions->pluck('name')->values()->all()])
            ->all();
    }

    public function render()
    {
        return view('livewire.settings.roles', [
            'roles' => Role::cases(),
            'groups' => Permission::grouped(),
        ]);
    }
}
