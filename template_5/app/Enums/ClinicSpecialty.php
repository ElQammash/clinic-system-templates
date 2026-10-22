<?php

namespace App\Enums;

enum ClinicSpecialty: string
{
    case General = 'general';
    case Dental = 'dental';
    case Pediatrics = 'pediatrics';
    case Dermatology = 'dermatology';
    case Ophthalmology = 'ophthalmology';
    case Gynecology = 'gynecology';
    case Orthopedics = 'orthopedics';
    case Ent = 'ent';
    case Cardiology = 'cardiology';
    case InternalMedicine = 'internal';
    case Physiotherapy = 'physiotherapy';
    case Nutrition = 'nutrition';

    public function label(): string
    {
        return __('specialties.'.$this->value);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
