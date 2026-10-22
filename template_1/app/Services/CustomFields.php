<?php

namespace App\Services;

use App\Enums\CustomFieldEntity;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use App\Support\Format;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Clinic-defined extra fields for patients and visits: definitions,
 * validation rules, value cleaning and display formatting.
 */
class CustomFields
{
    /** @return Collection<int, CustomField> */
    public function for(CustomFieldEntity $entity): Collection
    {
        return CustomField::query()->for($entity)->get();
    }

    /**
     * Validation rules keyed as "{$prefix}.{key}" (e.g. "custom.tooth_number").
     *
     * @return array<string, list<mixed>>
     */
    public function rules(CustomFieldEntity $entity, string $prefix = 'custom'): array
    {
        return $this->for($entity)->mapWithKeys(function (CustomField $field) use ($prefix) {
            $base = [$field->is_required && $field->type !== CustomFieldType::Checkbox ? 'required' : 'nullable'];

            $rules = match ($field->type) {
                CustomFieldType::Text => ['string', 'max:255'],
                CustomFieldType::Textarea => ['string', 'max:5000'],
                CustomFieldType::Number => ['numeric'],
                CustomFieldType::Date => ['date'],
                CustomFieldType::Checkbox => ['boolean'],
                CustomFieldType::Select => [Rule::in($field->optionList())],
                CustomFieldType::MultiSelect => ['array'],
            };

            $result = ["{$prefix}.{$field->key}" => [...$base, ...$rules]];

            if ($field->type === CustomFieldType::MultiSelect) {
                $result["{$prefix}.{$field->key}.*"] = [Rule::in($field->optionList())];
            }

            return $result;
        })->all();
    }

    /** @return array<string, string> */
    public function attributes(CustomFieldEntity $entity, string $prefix = 'custom'): array
    {
        return $this->for($entity)->mapWithKeys(fn (CustomField $f) => ["{$prefix}.{$f->key}" => $f->label()])->all();
    }

    /**
     * Keep only known keys, cast values, drop empty ones. Values of fields that are
     * no longer defined (e.g. deleted later) are preserved from $existing.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>|null
     */
    public function clean(CustomFieldEntity $entity, array $input, array $existing = []): ?array
    {
        $fields = $this->for($entity)->keyBy('key');
        $data = array_diff_key($existing, $fields->all());

        foreach ($fields as $key => $field) {
            $value = $input[$key] ?? null;

            $value = match ($field->type) {
                CustomFieldType::Number => filled($value) ? (float) Format::normalizeDigits((string) $value) : null,
                CustomFieldType::Checkbox => (bool) $value,
                CustomFieldType::MultiSelect => array_values(array_filter((array) $value)) ?: null,
                default => filled($value) ? trim((string) $value) : null,
            };

            if ($value !== null && $value !== false) {
                $data[$key] = $value;
            }
        }

        return $data === [] ? null : $data;
    }

    /**
     * Label/value pairs ready for display (only filled values).
     *
     * @param  array<string, mixed>|null  $data
     * @return list<array{label: string, value: string}>
     */
    public function display(CustomFieldEntity $entity, ?array $data): array
    {
        if (empty($data)) {
            return [];
        }

        return CustomField::query()->where('entity', $entity->value)->orderBy('sort')->get()
            ->filter(fn (CustomField $f) => array_key_exists($f->key, $data))
            ->map(fn (CustomField $f) => [
                'label' => $f->label(),
                'value' => match ($f->type) {
                    CustomFieldType::Checkbox => $data[$f->key] ? __('Yes') : __('No'),
                    CustomFieldType::MultiSelect => implode('، ', (array) $data[$f->key]),
                    CustomFieldType::Date => Format::date($data[$f->key]),
                    CustomFieldType::Number => Format::number($data[$f->key], fmod((float) $data[$f->key], 1.0) ? 1 : 0),
                    default => (string) $data[$f->key],
                },
            ])
            ->values()
            ->all();
    }
}
