<?php

namespace App\Enums;

enum MedicineForm: string
{
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Syrup = 'syrup';
    case Suspension = 'suspension';
    case Drops = 'drops';
    case Injection = 'injection';
    case Cream = 'cream';
    case Ointment = 'ointment';
    case Gel = 'gel';
    case Spray = 'spray';
    case Inhaler = 'inhaler';
    case Suppository = 'suppository';
    case Sachet = 'sachet';
    case Lotion = 'lotion';
    case Other = 'other';

    public function label(): string
    {
        return __('medicine_forms.'.$this->value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $f) => [$f->value => $f->label()])->all();
    }
}
