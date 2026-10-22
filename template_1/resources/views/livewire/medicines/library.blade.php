@php use App\Support\Format; @endphp
<div>
    <x-page-header :title="__('Medicines')" :subtitle="__('Your medicines library and ready-made prescription templates.')">
        <x-slot:actions>
            @if ($tab === 'medicines')
                @can('medicines.manage')
                    <button type="button" wire:click="edit" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add medicine') }}</button>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 inline-flex rounded-xl bg-white p-1 shadow-(--shadow-soft)">
        @foreach (['medicines' => __('Medicines library'), 'templates' => __('Prescription templates')] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['rounded-lg px-4 py-2 text-sm font-semibold transition', 'bg-primary-600 text-white shadow-sm' => $tab === $key, 'text-muted hover:text-ink' => $tab !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'medicines')
        <div class="card overflow-hidden">
            <div class="border-b border-line p-4">
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input wire:model.live.debounce.300ms="search" type="search" dir="auto" placeholder="{{ __('Search by trade or generic name') }}" class="form-control ps-10">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead><tr><th>{{ __('Medicine') }}</th><th class="hidden md:table-cell">{{ __('Form') }}</th><th class="hidden lg:table-cell">{{ __('Default directions') }}</th><th class="hidden sm:table-cell">{{ __('Prescribed') }}</th><th></th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($medicines as $medicine)
                            <tr wire:key="m-{{ $medicine->id }}" @class(['opacity-50' => ! $medicine->is_active])>
                                <td><span dir="ltr" class="font-semibold text-ink">{{ $medicine->displayName() }}</span>@if ($medicine->generic_name)<br><span dir="ltr" class="text-xs text-muted">{{ $medicine->generic_name }}</span>@endif</td>
                                <td class="hidden md:table-cell">{{ $medicine->form?->label() ?? '—' }}</td>
                                <td class="hidden text-xs lg:table-cell">{{ collect([$medicine->default_dose, $medicine->default_frequency, $medicine->default_duration])->filter()->implode(' — ') ?: '—' }}</td>
                                <td class="hidden sm:table-cell">{{ Format::number($medicine->times_prescribed) }}</td>
                                <td class="text-end whitespace-nowrap">
                                    @can('medicines.manage')
                                        <button type="button" wire:click="toggle({{ $medicine->id }})" class="btn btn-ghost btn-sm">{{ $medicine->is_active ? __('Disable') : __('Enable') }}</button>
                                        <button type="button" wire:click="edit({{ $medicine->id }})" class="btn btn-secondary btn-sm"><x-icon name="edit" class="size-4" /></button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-14 text-center text-muted">{{ __('No medicines found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($medicines->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $medicines->links() }}</div>
            @endif
        </div>

        <x-modal wire:model="showForm" :title="$editingId ? __('Edit medicine') : __('Add medicine')">
            <form id="med-form" wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Trade name')" for="m-name" error="medicine.name" required><input wire:model="medicine.name" id="m-name" dir="ltr" class="form-control text-start"></x-field>
                <x-field :label="__('Generic name')" for="m-generic" error="medicine.generic_name"><input wire:model="medicine.generic_name" id="m-generic" dir="ltr" class="form-control text-start"></x-field>
                <x-field :label="__('Form')" for="m-form" error="medicine.form">
                    <select wire:model="medicine.form" id="m-form" class="form-control">
                        <option value="">—</option>
                        @foreach ($forms as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </x-field>
                <x-field :label="__('Strength')" for="m-strength" error="medicine.strength"><input wire:model="medicine.strength" id="m-strength" dir="ltr" class="form-control text-start" placeholder="500mg"></x-field>
                <x-field :label="__('Default dose')" for="m-dose" error="medicine.default_dose"><input wire:model="medicine.default_dose" id="m-dose" class="form-control"></x-field>
                <x-field :label="__('Default frequency')" for="m-freq" error="medicine.default_frequency"><input wire:model="medicine.default_frequency" id="m-freq" class="form-control"></x-field>
                <x-field :label="__('Default duration')" for="m-dur" error="medicine.default_duration"><input wire:model="medicine.default_duration" id="m-dur" class="form-control"></x-field>
                <x-field :label="__('Notes')" for="m-notes" error="medicine.notes"><input wire:model="medicine.notes" id="m-notes" class="form-control"></x-field>
            </form>
            <x-slot:footer>
                <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
                <button type="submit" form="med-form" class="btn btn-primary">{{ __('Save') }}</button>
            </x-slot:footer>
        </x-modal>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($templates as $template)
                <div class="card p-5" wire:key="t-{{ $template->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink">{{ $template->name }}</p>
                            <p class="text-xs text-muted">{{ $template->doctor ? $template->doctor->displayName() : __('Shared with all doctors') }}</p>
                        </div>
                        @if ($template->doctor_id === auth()->id() || auth()->user()->isAdmin())
                            <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="{{ __('Delete this template?') }}" class="btn btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /></button>
                        @endif
                    </div>
                    <ol class="mt-3 list-inside list-decimal space-y-1 text-sm">
                        @foreach ($template->items as $item)
                            <li><span dir="ltr" class="font-medium">{{ $item->medicine_name }}</span> <span class="text-xs text-muted">{{ collect([$item->dose, $item->frequency, $item->duration])->filter()->implode(' — ') }}</span></li>
                        @endforeach
                    </ol>
                </div>
            @empty
                <div class="card px-6 py-14 text-center text-sm text-muted md:col-span-2 xl:col-span-3">{{ __('No templates yet. Write a prescription in a visit and choose “Save as template”.') }}</div>
            @endforelse
        </div>
    @endif
</div>
