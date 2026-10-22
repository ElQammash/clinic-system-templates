<?php

namespace App\Enums;

enum LabTestType: string
{
    case Lab = 'lab';
    case Imaging = 'imaging';

    public function label(): string
    {
        return match ($this) {
            self::Lab => __('Lab test'),
            self::Imaging => __('Imaging'),
        };
    }
}
