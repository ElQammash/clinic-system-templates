<?php

namespace App\Livewire\Settings;

use App\Actions\Users\SaveUser;
use App\Enums\ClinicSpecialty;
use App\Enums\Role;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Users extends Component
{
    use WithPagination;

    public UserForm $form;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $roleFilter = '';

    public bool $showForm = false;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->form->reset();
        $this->form->resetValidation();
        $this->showForm = true;
    }

    public function edit(User $user): void
    {
        $this->authorize('update', $user);

        $this->form->resetValidation();
        $this->form->setUser($user->load('roles', 'doctorProfile'));
        $this->showForm = true;
    }

    public function save(SaveUser $action): void
    {
        $this->form->user
            ? $this->authorize('update', $this->form->user)
            : $this->authorize('create', User::class);

        [$data, $profile] = $this->form->payload();

        $action->handle($this->form->user, $data, $profile, auth()->user());

        $this->showForm = false;
        $this->form->reset();
        $this->dispatch('toast', message: __('User saved.'));
    }

    public function toggleActive(User $user, SaveUser $action): void
    {
        $this->authorize('update', $user);

        $action->toggleActive($user, auth()->user());

        $this->dispatch('toast', message: $user->is_active ? __('User activated.') : __('User deactivated.'));
    }

    public function render()
    {
        $users = User::query()
            ->with('roles', 'doctorProfile')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")))
            ->when(Role::tryFrom($this->roleFilter), fn ($q, Role $role) => $q->role($role->value))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.settings.users', [
            'users' => $users,
            'roles' => Role::options(),
            'specialties' => ClinicSpecialty::options(),
        ]);
    }
}
