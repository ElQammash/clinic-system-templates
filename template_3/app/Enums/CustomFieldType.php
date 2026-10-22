<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case MultiSelect = 'multiselect';
    case Checkbox = 'checkbox';
    case Date = 'date';

    public function label(): string
    {
        return match ($this) {
            self::Text => __('Short text'),
            self::Textarea => __('Long text'),
            self::Number => __('Number'),
            self::Select => __('Single choice'),
            self::MultiSelect => __('Multiple choice'),
            self::Checkbox => __('Yes / No'),
            self::Date => __('Date'),
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
