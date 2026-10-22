<?php

namespace App\Enums;

enum AttachmentCategory: string
{
    case Xray = 'xray';
    case Lab = 'lab';
    case Image = 'image';
    case Report = 'report';
    case Prescription = 'prescription';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Xray => __('X-ray / scan'),
            self::Lab => __('Lab result'),
            self::Image => __('Photo'),
            self::Report => __('Medical report'),
            self::Prescription => __('Old prescription'),
            self::Other => __('Other'),
        };
    }

    /** Semantic icon key (each template maps it to its icon set). */
    public function icon(): string
    {
        return match ($this) {
            self::Xray => 'xray',
            self::Lab => 'lab',
            self::Image => 'image',
            self::Report, self::Prescription => 'document',
            self::Other => 'paperclip',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Xray => 'blue',
            self::Lab => 'violet',
            self::Image => 'emerald',
            self::Report => 'amber',
            self::Prescription => 'primary',
            self::Other => 'slate',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
