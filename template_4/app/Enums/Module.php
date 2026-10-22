<?php

namespace App\Enums;

/**
 * Optional modules that a clinic can switch on/off from Settings.
 */
enum Module: string
{
    case Lab = 'lab';
    case Expenses = 'expenses';
    case Inventory = 'inventory';

    public function settingKey(): string
    {
        return 'module_'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Lab => __('Lab & imaging'),
            self::Expenses => __('Expenses'),
            self::Inventory => __('Inventory'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Lab => __('Request lab tests and imaging, and upload their results to the patient file.'),
            self::Expenses => __('Track clinic expenses by category and compare them with income.'),
            self::Inventory => __('Track consumables and medical supplies with low-stock alerts.'),
        };
    }

    public function enabled(): bool
    {
        return (bool) settings($this->settingKey());
    }
}
