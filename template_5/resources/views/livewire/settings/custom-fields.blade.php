@php use App\Enums\CustomFieldType; @endphp
<div>
    <x-page-header :title="__('Custom fields')" :subtitle="__('Add your own fields to the patient file and the visit form — no programming needed.')">
        <x-slot:actions>
            @if ($hasPresets)
                <button type="button" wire:click="applyPresets" class="btn btn-secondary"><x-icon name="sparkles" class="size-4" /> {{ __('Add suggested fields for :type', ['type' => __('specialties.'.settings('specialty'))]) }}</button>
            @endif
            <button type="button" wire:click="create" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add field') }}</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 inline-flex rounded-2xl bg-white p-1 shadow-(--shadow-soft)">
        @foreach ($entities as $value => $label)
            <button type="button" wire:click="$set('entity', '{{ $value }}')" @class([
                'rounded-xl px-4 py-2 text-sm font-semibold transition',
                'bg-primary-600 text-white shadow-sm' => $entity === $value,
                'text-muted hover:text-ink' => $entity !== $value,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        @if ($fields->isEmpty())
            <div class="px-6 py-14 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-full bg-primary-50 text-primary-600"><x-icon name="fields" class="size-7" /></div>
                <p class="mt-3 font-semibold text-ink">{{ __('No custom fields yet') }}</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-muted">{{ __('Examples: tooth number for dental clinics, visual acuity for eye clinics, skin type for dermatology.') }}</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($fields as $field)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4" wire:key="cf-{{ $field->id }}">
                        <div class="flex flex-col">
                            <button type="button" wire:click="move({{ $field->id }}, -1)" class="text-slate-600 hover:text-primary-600 disabled:opacity-30" @disabled($loop->first) aria-label="{{ __('Move up') }}"><x-icon name="up" class="size-4" /></button>
                            <button type="button" wire:click="move({{ $field->id }}, 1)" class="text-slate-600 hover:text-primary-600 disabled:opacity-30" @disabled($loop->last) aria-label="{{ __('Move down') }}"><x-icon name="down" class="size-4" /></button>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p @class(['font-semibold', 'text-ink' => $field->is_active, 'text-muted line-through' => ! $field->is_active])>
                                {{ $field->label() }}
                                @if ($field->is_required)<span class="text-rose-500">*</span>@endif
                            </p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted">
                                <x-badge>{{ $field->type->label() }}</x-badge>
                                @if ($field->specialty)<x-badge color="violet">{{ $field->specialty->label() }}</x-badge>@endif
                                @if ($field->type->hasOptions())<span>{{ implode('، ', array_slice($field->optionList(), 0, 5)) }}{{ count($field->optionList()) > 5 ? '…' : '' }}</span>@endif
                                <span class="font-mono" dir="ltr">{{ $field->key }}</span>
                            </p>
                        </div>
                        <button type="button" wire:click="toggle({{ $field->id }})" class="btn btn-ghost btn-sm">{{ $field->is_active ? __('Disable') : __('Enable') }}</button>
                        <button type="button" wire:click="edit({{ $field->id }})" class="btn btn-secondary btn-sm"><x-icon name="edit" class="size-4" /> {{ __('Edit') }}</button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-modal wire:model="showForm" :title="$editingId ? __('Edit field') : __('Add field')">
        <form id="cf-form" wire:submit="save" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Label (Arabic)')" for="cf-label-ar" error="label_ar" required>
                    <input wire:model="label_ar" id="cf-label-ar" class="form-control" placeholder="{{ __('e.g. Tooth number') }}">
                </x-field>
                <x-field :label="__('Label (English)')" for="cf-label-en" error="label_en">
                    <input wire:model="label_en" id="cf-label-en" dir="ltr" class="form-control text-start">
                </x-field>
                <x-field :label="__('Field type')" for="cf-type" error="type">
                    <select wire:model.live="type" id="cf-type" class="form-control">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('Show for clinic type')" for="cf-specialty" error="specialty">
                    <select wire:model="specialty" id="cf-specialty" class="form-control">
                        <option value="">{{ __('All clinic types') }}</option>
                        @foreach ($specialties as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            @if (CustomFieldType::from($type)->hasOptions())
                <x-field :label="__('Options')" for="cf-options" error="optionsText" :hint="__('One option per line.')">
                    <textarea wire:model="optionsText" id="cf-options" rows="5" class="form-control"></textarea>
                </x-field>
            @endif

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="is_required" class="form-check"> {{ __('Required') }}</label>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="is_active" class="form-check"> {{ __('Active') }}</label>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="cf-form" class="btn btn-primary">{{ __('Save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
