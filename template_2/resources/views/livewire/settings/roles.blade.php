@php use App\Enums\Permission; use App\Enums\Role; @endphp
<div>
    <x-page-header :title="__('Roles & permissions')" :subtitle="__('Choose what each role can see and do. The admin role always has full access.')">
        <x-slot:actions>
            <button type="button" wire:click="save" class="btn btn-primary" wire:loading.attr="disabled"><x-icon name="check" class="size-4" /> {{ __('Save changes') }}</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead class="sticky top-0">
                    <tr>
                        <th class="min-w-64">{{ __('Permission') }}</th>
                        @foreach ($roles as $role)
                            <th class="text-center">
                                <span class="block">{{ $role->label() }}</span>
                                @if ($role !== Role::Admin)
                                    <button type="button" wire:click="resetToDefaults('{{ $role->value }}')" class="mt-1 text-[11px] font-medium text-primary-700 hover:underline">{{ __('Defaults') }}</button>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($groups as $group => $permissions)
                        <tr class="bg-subtle hover:bg-slate-50/60">
                            <td colspan="{{ count($roles) + 1 }}" class="text-xs font-bold tracking-wide text-muted">{{ Permission::groupLabel($group) }}</td>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr wire:key="perm-{{ $permission->value }}">
                                <td>
                                    <span class="text-sm text-ink">{{ $permission->label() }}</span>
                                    <span class="ms-2 hidden font-mono text-[11px] text-muted xl:inline" dir="ltr">{{ $permission->value }}</span>
                                </td>
                                @foreach ($roles as $role)
                                    <td class="text-center">
                                        @if ($role === Role::Admin)
                                            <x-icon name="check" class="mx-auto size-4 text-primary-600" />
                                        @else
                                            <input type="checkbox" class="form-check" value="{{ $permission->value }}"
                                                   wire:model="matrix.{{ $role->value }}" aria-label="{{ $role->label() }}: {{ $permission->label() }}">
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
