@php use App\Support\Format; @endphp
<div>
    <x-page-header :title="__('Schedule & visit types')" :subtitle="__('Working hours, days off and the types of visits patients can book.')" />

    <div class="mb-5 inline-flex rounded bg-surface p-1 shadow-(--shadow-soft)">
        @foreach (['hours' => __('Working hours'), 'off' => __('Days off'), 'types' => __('Visit types')] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                'rounded-sm px-4 py-2 text-sm font-semibold transition',
                'bg-primary-600 text-white shadow-none' => $tab === $key,
                'text-muted hover:text-ink' => $tab !== $key,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'hours')
        <div class="card">
            <div class="flex flex-col gap-3 border-b border-line p-5 sm:flex-row sm:items-center">
                <x-field :label="__('Hours for')" for="hours-for" class="sm:w-72">
                    <select wire:model.live="hoursFor" id="hours-for" class="form-control">
                        <option value="0">{{ __('Clinic (default for all doctors)') }}</option>
                        @foreach ($this->doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->displayName() }}</option>
                        @endforeach
                    </select>
                </x-field>
                @if ($hoursFor)
                    <p class="text-sm text-muted sm:mt-6">
                        @if ($hasOwnHours)
                            {{ __('This doctor has their own hours.') }}
                            <button type="button" wire:click="useClinicDefaults" wire:confirm="{{ __('Remove this doctor\'s own hours and use the clinic hours?') }}" class="font-semibold text-primary-700 hover:underline">{{ __('Use clinic hours') }}</button>
                        @else
                            {{ __('Currently following the clinic hours. Saving creates hours for this doctor only.') }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="divide-y divide-line">
                @foreach ($days as $day => $dayName)
                    <div class="flex flex-col gap-3 px-5 py-3 sm:flex-row sm:items-start" wire:key="day-{{ $hoursFor }}-{{ $day }}">
                        <div class="flex w-32 shrink-0 items-center gap-2 pt-2">
                            <span @class(['size-2 rounded-full', 'bg-primary-500' => count($shifts[$day] ?? []), 'bg-slate-300' => ! count($shifts[$day] ?? [])])></span>
                            <span class="font-semibold text-ink">{{ $dayName }}</span>
                        </div>
                        <div class="flex-1 space-y-2">
                            @forelse ($shifts[$day] ?? [] as $i => $shift)
                                <div class="flex flex-wrap items-center gap-2" wire:key="shift-{{ $day }}-{{ $i }}">
                                    <input type="time" wire:model="shifts.{{ $day }}.{{ $i }}.from" class="form-control w-36 py-2">
                                    <span class="text-muted">—</span>
                                    <input type="time" wire:model="shifts.{{ $day }}.{{ $i }}.to" class="form-control w-36 py-2">
                                    <button type="button" wire:click="removeShift({{ $day }}, {{ $i }})" class="btn btn-ghost p-2 text-muted hover:text-rose-600" aria-label="{{ __('Remove') }}"><x-icon name="close" class="size-4" /></button>
                                </div>
                            @empty
                                <p class="pt-2 text-sm text-muted">{{ __('Day off') }}</p>
                            @endforelse
                            @error("shifts.$day") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-1 sm:pt-1">
                            <button type="button" wire:click="addShift({{ $day }})" class="btn btn-ghost btn-sm text-primary-700"><x-icon name="plus" class="size-4" /> {{ __('Shift') }}</button>
                            @if (count($shifts[$day] ?? []))
                                <button type="button" wire:click="copyToAllDays({{ $day }})" class="btn btn-ghost btn-sm" title="{{ __('Copy to all days except Friday') }}">{{ __('Copy to all') }}</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end border-t border-line bg-subtle px-5 py-3">
                <button type="button" wire:click="saveHours" class="btn btn-primary"><x-icon name="check" class="size-4" /> {{ __('Save working hours') }}</button>
            </div>
        </div>
    @endif

    @if ($tab === 'off')
        <div class="grid gap-6 lg:grid-cols-3">
            <form wire:submit="addBlockedPeriod" class="card space-y-4 p-5">
                <h2 class="font-semibold text-ink">{{ __('Add days off') }}</h2>
                <x-field :label="__('For')" for="block-doctor" error="blockDoctorId">
                    <select wire:model="blockDoctorId" id="block-doctor" class="form-control">
                        <option value="">{{ __('Whole clinic (e.g. public holiday)') }}</option>
                        @foreach ($this->doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->displayName() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field :label="__('From')" for="block-from" error="blockFrom"><input wire:model="blockFrom" id="block-from" type="date" class="form-control"></x-field>
                    <x-field :label="__('To')" for="block-to" error="blockTo"><input wire:model="blockTo" id="block-to" type="date" class="form-control"></x-field>
                </div>
                <x-field :label="__('Reason')" for="block-reason" error="blockReason">
                    <input wire:model="blockReason" id="block-reason" class="form-control" placeholder="{{ __('e.g. Eid holiday, conference') }}">
                </x-field>
                <button type="submit" class="btn btn-primary w-full">{{ __('Save') }}</button>
            </form>

            <div class="card overflow-hidden lg:col-span-2">
                <ul class="divide-y divide-line">
                    @forelse ($blockedPeriods as $period)
                        <li class="flex items-center gap-4 px-5 py-3" wire:key="bp-{{ $period->id }}">
                            <span class="grid size-10 place-items-center rounded bg-rose-50 text-rose-600"><x-icon name="calendar" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-ink">{{ $period->reason ?: __('Not available') }}</p>
                                <p class="text-xs text-muted">{{ Format::date($period->starts_at) }} → {{ Format::date($period->ends_at) }} · {{ $period->doctor?->displayName() ?? __('Whole clinic') }}</p>
                            </div>
                            <button type="button" wire:click="deleteBlockedPeriod({{ $period->id }})" wire:confirm="{{ __('Delete?') }}" class="btn btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /></button>
                        </li>
                    @empty
                        <li class="px-5 py-12 text-center text-sm text-muted">{{ __('No upcoming days off.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif

    @if ($tab === 'types')
        <div class="card overflow-hidden">
            <div class="flex justify-end border-b border-line p-4">
                <button type="button" wire:click="editType" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add visit type') }}</button>
            </div>
            <table class="table-clean">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Price') }}</th><th>{{ __('Duration') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ($visitTypes as $type)
                        <tr wire:key="vt-{{ $type->id }}" @class(['opacity-50' => ! $type->is_active])>
                            <td><span class="me-2 inline-block size-3 rounded-full align-middle" style="background: {{ $type->color }}"></span><span class="font-medium text-ink">{{ $type->name() }}</span></td>
                            <td class="font-semibold">{{ money($type->default_price) }}</td>
                            <td>{{ $type->default_duration ? trans_choice(':count min|:count min', $type->default_duration, ['count' => Format::number($type->default_duration)]) : __('Doctor\'s slot length') }}</td>
                            <td>@if ($type->is_active)<x-badge color="emerald">{{ __('Active') }}</x-badge>@else<x-badge>{{ __('Inactive') }}</x-badge>@endif</td>
                            <td class="text-end whitespace-nowrap">
                                <button type="button" wire:click="toggleType({{ $type->id }})" class="btn btn-ghost btn-sm">{{ $type->is_active ? __('Disable') : __('Enable') }}</button>
                                <button type="button" wire:click="editType({{ $type->id }})" class="btn btn-secondary btn-sm"><x-icon name="edit" class="size-4" /> {{ __('Edit') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-modal wire:model="showTypeForm" :title="$typeId ? __('Edit visit type') : __('Add visit type')" max-width="max-w-lg">
            <form id="type-form" wire:submit="saveType" class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Name (Arabic)')" for="vt-ar" error="typeNameAr" required><input wire:model="typeNameAr" id="vt-ar" class="form-control"></x-field>
                <x-field :label="__('Name (English)')" for="vt-en" error="typeNameEn"><input wire:model="typeNameEn" id="vt-en" dir="ltr" class="form-control text-start"></x-field>
                <x-field :label="__('Price')" for="vt-price" error="typePrice" required><input wire:model="typePrice" id="vt-price" type="number" min="0" step="0.01" dir="ltr" class="form-control text-start"></x-field>
                <x-field :label="__('Duration (minutes)')" for="vt-duration" error="typeDuration" :hint="__('Empty = the doctor\'s slot length')"><input wire:model="typeDuration" id="vt-duration" type="number" min="5" step="5" dir="ltr" class="form-control text-start"></x-field>
                <x-field :label="__('Color')" for="vt-color" error="typeColor"><input wire:model="typeColor" id="vt-color" type="color" class="h-10 w-20 cursor-pointer rounded-sm border border-line bg-surface p-1"></x-field>
            </form>
            <x-slot:footer>
                <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
                <button type="submit" form="type-form" class="btn btn-primary">{{ __('Save') }}</button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
