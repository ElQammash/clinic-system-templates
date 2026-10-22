<?php

namespace App\Livewire\Settings;

use App\Enums\ClinicSpecialty;
use App\Enums\CustomFieldEntity;
use App\Enums\CustomFieldType;
use App\Enums\Permission;
use App\Models\CustomField;
use App\Support\CustomFieldPresets;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Custom fields')]
class CustomFields extends Component
{
    #[Url]
    public string $entity = 'visit';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label_ar = '';

    public ?string $label_en = null;

    public string $type = 'text';

    /** One option per line */
    public string $optionsText = '';

    public bool $is_required = false;

    public ?string $specialty = null;

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);
    }

    public function create(): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);
        $this->reset('editingId', 'label_ar', 'label_en', 'type', 'optionsText', 'is_required', 'specialty', 'is_active');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);
        $field = CustomField::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $field->id;
        $this->label_ar = $field->label_ar;
        $this->label_en = $field->label_en;
        $this->type = $field->type->value;
        $this->optionsText = implode("\n", $field->optionList());
        $this->is_required = $field->is_required;
        $this->specialty = $field->specialty?->value;
        $this->is_active = $field->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);
        $entity = CustomFieldEntity::from($this->entity);

        $data = $this->validate([
            'label_ar' => ['required', 'string', 'max:100'],
            'label_en' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::enum(CustomFieldType::class)],
            'optionsText' => [Rule::requiredIf(fn () => CustomFieldType::from($this->type)->hasOptions()), 'nullable', 'string', 'max:3000'],
            'is_required' => ['boolean'],
            'specialty' => ['nullable', Rule::enum(ClinicSpecialty::class)],
            'is_active' => ['boolean'],
        ], attributes: ['optionsText' => __('Options'), 'label_ar' => __('Label (Arabic)')]);

        $type = CustomFieldType::from($data['type']);
        $options = $type->hasOptions()
            ? array_values(array_unique(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $data['optionsText'])))))
            : null;

        $attributes = [
            'label_ar' => $data['label_ar'],
            'label_en' => $data['label_en'],
            'type' => $type,
            'options' => $options,
            'is_required' => $data['is_required'],
            'specialty' => $data['specialty'] ?: null,
            'is_active' => $data['is_active'],
        ];

        if ($this->editingId) {
            // The key never changes so stored values stay attached to the field.
            CustomField::findOrFail($this->editingId)->update($attributes);
        } else {
            CustomField::create($attributes + [
                'entity' => $entity,
                'key' => $this->uniqueKey($entity, $data['label_en'] ?: $data['label_ar']),
                'sort' => (int) CustomField::where('entity', $entity->value)->max('sort') + 1,
            ]);
        }

        activity('settings')->event('updated')->withProperties(['custom_field' => $data['label_ar'], 'entity' => $entity->value])->log('custom field saved');

        $this->showForm = false;
        $this->dispatch('toast', message: __('Field saved.'));
    }

    public function move(int $id, int $direction): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);

        $fields = CustomField::where('entity', $this->entity)->orderBy('sort')->orderBy('id')->get()->values();
        $index = $fields->search(fn (CustomField $f) => $f->id === $id);
        $swap = $index === false ? null : $fields->get($index + $direction);

        if ($swap === null) {
            return;
        }

        $fields[$index] = $swap;
        $fields[$index + $direction] = CustomField::find($id);
        $fields->each(fn (CustomField $f, int $i) => $f->update(['sort' => $i]));
    }

    /** Add the suggested visit fields for the clinic's current specialty. */
    public function applyPresets(): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);

        $added = CustomFieldPresets::apply(ClinicSpecialty::from(settings('specialty', 'general')));
        $this->entity = CustomFieldEntity::Visit->value;

        $this->dispatch('toast', message: trans_choice('No new fields were added.|:count field added.|:count fields added.', $added, ['count' => $added]));
    }

    public function toggle(int $id): void
    {
        $this->authorize(Permission::CustomFieldsManage->value);
        $field = CustomField::findOrFail($id);
        $field->update(['is_active' => ! $field->is_active]);
    }

    protected function uniqueKey(CustomFieldEntity $entity, string $label): string
    {
        $base = Str::slug(Str::ascii($label), '_') ?: 'field';
        $base = Str::limit($base, 50, '');
        $key = $base;
        $i = 2;

        while (CustomField::where('entity', $entity->value)->where('key', $key)->exists()) {
            $key = $base.'_'.$i++;
        }

        return $key;
    }

    public function render()
    {
        return view('livewire.settings.custom-fields', [
            'fields' => CustomField::where('entity', $this->entity)->orderBy('sort')->orderBy('id')->get(),
            'entities' => collect(CustomFieldEntity::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]),
            'types' => CustomFieldType::options(),
            'specialties' => ClinicSpecialty::options(),
            'hasPresets' => CustomFieldPresets::for(ClinicSpecialty::from(settings('specialty', 'general'))) !== [],
        ]);
    }
}
