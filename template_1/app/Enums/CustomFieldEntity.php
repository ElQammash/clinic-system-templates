<?php

namespace App\Enums;

enum CustomFieldEntity: string
{
    case Patient = 'patient';
    case Visit = 'visit';

    public function label(): string
    {
        return match ($this) {
            self::Patient => __('Patient file'),
            self::Visit => __('Visit form'),
        };
    }
}
