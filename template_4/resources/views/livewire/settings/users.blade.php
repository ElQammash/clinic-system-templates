@php use App\Enums\Role; @endphp
<div>
    <x-page-header :title="__('Users')" :subtitle="__('Doctors, reception and accounting staff who can log in.')">
        <x-slot:actions>
            <button type="button" wire:click="create" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add user') }}</button>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-neutral-400" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search by name, e-mail or phone') }}" class="form-control ps-10">
            </div>
            <select wire:model.live="roleFilter" class="form-control sm:w-48">
                <option value="">{{ __('All roles') }}</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th class="hidden md:table-cell">{{ __('Phone') }}</th>
                        <th class="hidden lg:table-cell">{{ __('Last login') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($users as $member)
                        @php $role = $member->primaryRole(); @endphp
                        <tr wire:key="user-{{ $member->id }}" @class(['opacity-60' => ! $member->is_active])>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full text-sm font-semibold text-white"
                                          style="background: {{ $member->doctorProfile?->color ?? '#94a3b8' }}">{{ $member->initials() }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-ink">{{ $member->displayName() }}</p>
                                        <p class="truncate text-xs text-muted" dir="ltr">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($role)
                                    <x-badge :color="$role->color()">{{ $role->label() }}</x-badge>
                                @endif
                                @if ($member->doctorProfile?->title())
                                    <p class="mt-1 text-xs text-muted">{{ $member->doctorProfile->title() }}</p>
                                @endif
                            </td>
                            <td class="hidden md:table-cell"><span class="ltr-nums">{{ $member->phone }}</span></td>
                            <td class="hidden text-xs text-muted lg:table-cell">{{ $member->last_login_at?->diffForHumans() ?? '—' }}</td>
                            <td>
                                @if ($member->is_active)
                                    <x-badge color="emerald">{{ __('Active') }}</x-badge>
                                @else
                                    <x-badge>{{ __('Inactive') }}</x-badge>
                                @endif
                            </td>
                            <td class="text-end whitespace-nowrap">
                                <button type="button" wire:click="edit({{ $member->id }})" class="btn btn-ghost btn-sm"><x-icon name="edit" class="size-4" /> {{ __('Edit') }}</button>
                                @unless ($member->is(auth()->user()))
                                    <button type="button" wire:click="toggleActive({{ $member->id }})"
                                            wire:confirm="{{ $member->is_active ? __('Deactivate this user? They will not be able to log in.') : __('Activate this user?') }}"
                                            class="btn btn-ghost btn-sm {{ $member->is_active ? 'text-rose-600' : 'text-emerald-700' }}">
                                        {{ $member->is_active ? __('Deactivate') : __('Activate') }}
                                    </button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-muted">{{ __('No users found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>

    <x-modal wire:model="showForm" :title="$form->user ? __('Edit user') : __('Add user')">
        <form id="user-form" wire:submit="save" class="space-y-5">
            @error('role') <div class="rounded-none bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div> @enderror
            @error('is_active') <div class="rounded-none bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div> @enderror

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Full name')" for="u-name" error="form.name" required>
                    <input wire:model="form.name" id="u-name" @class(['form-control', 'is-invalid' => $errors->has('form.name')])>
                </x-field>
                <x-field :label="__('Role')" for="u-role" error="form.role" required>
                    <select wire:model.live="form.role" id="u-role" class="form-control">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('E-mail')" for="u-email" error="form.email" required>
                    <input wire:model="form.email" id="u-email" type="email" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.email')])>
                </x-field>
                <x-field :label="__('Phone')" for="u-phone" error="form.phone">
                    <input wire:model="form.phone" id="u-phone" type="tel" dir="ltr" placeholder="01xxxxxxxxx" @class(['form-control text-start', 'is-invalid' => $errors->has('form.phone')])>
                </x-field>
                <x-field :label="$form->user ? __('New password') : __('Password')" for="u-password" error="form.password" :required="! $form->user"
                         :hint="$form->user ? __('Leave empty to keep the current password.') : __('At least 8 characters with letters and numbers.')">
                    <input wire:model="form.password" id="u-password" type="password" autocomplete="new-password" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.password')])>
                </x-field>
                <x-field :label="__('Language')" for="u-locale" error="form.locale">
                    <select wire:model="form.locale" id="u-locale" class="form-control">
                        <option value="">{{ __('Clinic default') }}</option>
                        @foreach (config('clinic.locales') as $code => $locale)
                            <option value="{{ $code }}">{{ $locale['name'] }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            @if ($form->role === Role::Doctor->value)
                <fieldset class="rounded-none border border-primary-200 bg-primary-50/40 p-4">
                    <legend class="px-2 text-sm font-semibold text-primary-800">{{ __('Doctor profile') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Title (Arabic)')" for="u-title-ar" error="form.title_ar">
                            <input wire:model="form.title_ar" id="u-title-ar" class="form-control" placeholder="{{ __('e.g. Pediatrics Consultant') }}">
                        </x-field>
                        <x-field :label="__('Title (English)')" for="u-title-en" error="form.title_en">
                            <input wire:model="form.title_en" id="u-title-en" dir="ltr" class="form-control text-start">
                        </x-field>
                        <x-field :label="__('Specialty')" for="u-specialty" error="form.specialty">
                            <select wire:model="form.specialty" id="u-specialty" class="form-control">
                                <option value="">—</option>
                                @foreach ($specialties as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field :label="__('License number')" for="u-license" error="form.license_number">
                            <input wire:model="form.license_number" id="u-license" dir="ltr" class="form-control text-start">
                        </x-field>
                        <x-field :label="__('Slot duration (minutes)')" for="u-slot" error="form.slot_minutes" required>
                            <input wire:model="form.slot_minutes" id="u-slot" type="number" min="5" max="240" step="5" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.slot_minutes')])>
                        </x-field>
                        <x-field :label="__('Consultation fee')" for="u-fee" error="form.consultation_fee" required>
                            <input wire:model="form.consultation_fee" id="u-fee" type="number" min="0" step="0.01" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('form.consultation_fee')])>
                        </x-field>
                        <x-field :label="__('Calendar color')" for="u-color" error="form.color">
                            <input wire:model="form.color" id="u-color" type="color" class="h-10 w-20 cursor-pointer rounded-none border border-neutral-300 bg-white p-1">
                        </x-field>
                    </div>
                </fieldset>
            @endif

            @if ($form->user && ! $form->user->is(auth()->user()))
                <label class="flex items-center gap-2 text-sm text-neutral-700">
                    <input type="checkbox" wire:model="form.is_active" class="form-check"> {{ __('Account is active') }}
                </label>
            @endif
        </form>

        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="user-form" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
