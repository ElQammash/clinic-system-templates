{{-- Renders one clinic-defined custom field. Expects $field (CustomField) and $model (e.g. "form.custom"). --}}
@php
    use App\Enums\CustomFieldType;
    $name = "{$model}.{$field->key}";
    $id = 'cf-'.$field->entity->value.'-'.$field->key;
    $wide = in_array($field->type, [CustomFieldType::Textarea, CustomFieldType::MultiSelect], true);
@endphp
<x-field :label="$field->type === CustomFieldType::Checkbox ? null : $field->label()" :for="$id" :error="$name" :required="$field->is_required" :class="$wide ? 'sm:col-span-2' : ''">
    @switch($field->type)
        @case(CustomFieldType::Textarea)
            <textarea wire:model="{{ $name }}" id="{{ $id }}" rows="3" @class(['form-control', 'is-invalid' => $errors->has($name)])></textarea>
            @break
        @case(CustomFieldType::Number)
            <input wire:model="{{ $name }}" id="{{ $id }}" type="number" step="any" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has($name)])>
            @break
        @case(CustomFieldType::Date)
            <input wire:model="{{ $name }}" id="{{ $id }}" type="date" @class(['form-control', 'is-invalid' => $errors->has($name)])>
            @break
        @case(CustomFieldType::Select)
            <select wire:model="{{ $name }}" id="{{ $id }}" @class(['form-control', 'is-invalid' => $errors->has($name)])>
                <option value="">—</option>
                @foreach ($field->optionList() as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            @break
        @case(CustomFieldType::MultiSelect)
            <div class="flex flex-wrap gap-2">
                @foreach ($field->optionList() as $option)
                    <label class="cursor-pointer rounded-full border border-slate-300 px-3 py-1.5 text-sm text-slate-600 transition has-checked:border-primary-500 has-checked:bg-primary-50 has-checked:text-primary-800">
                        <input type="checkbox" wire:model="{{ $name }}" value="{{ $option }}" class="sr-only"> {{ $option }}
                    </label>
                @endforeach
            </div>
            @break
        @case(CustomFieldType::Checkbox)
            <label class="flex items-center gap-2 pt-7 text-sm text-slate-700">
                <input wire:model="{{ $name }}" id="{{ $id }}" type="checkbox" class="form-check"> {{ $field->label() }}
            </label>
            @break
        @default
            <input wire:model="{{ $name }}" id="{{ $id }}" @class(['form-control', 'is-invalid' => $errors->has($name)])>
    @endswitch
</x-field>
