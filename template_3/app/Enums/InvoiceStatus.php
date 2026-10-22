<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => __('Unpaid'),
            self::PartiallyPaid => __('Partially paid'),
            self::Paid => __('Paid'),
            self::Void => __('Void'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'rose',
            self::PartiallyPaid => 'amber',
            self::Paid => 'emerald',
            self::Void => 'slate',
        };
    }

    /** Status from the amounts (minor units). */
    public static function fromAmounts(int $total, int $paid): self
    {
        return match (true) {
            $paid <= 0 && $total > 0 => self::Unpaid,
            $paid >= $total => self::Paid,
            default => self::PartiallyPaid,
        };
    }
}
