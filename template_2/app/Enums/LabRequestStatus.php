<?php

namespace App\Enums;

enum LabRequestStatus: string
{
    case Requested = 'requested';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => __('Awaiting results'),
            self::Completed => __('Results received'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::Completed => 'emerald',
            self::Cancelled => 'slate',
        };
    }
}
