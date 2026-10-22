<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case InstaPay = 'instapay';
    case Wallet = 'wallet';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Cash'),
            self::Card => __('Card'),
            self::InstaPay => __('InstaPay'),
            self::Wallet => __('Mobile wallet'),
            self::BankTransfer => __('Bank transfer'),
        };
    }

    /** Non-cash methods usually have a transaction reference. */
    public function needsReference(): bool
    {
        return $this !== self::Cash;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $m) => [$m->value => $m->label()])->all();
    }
}
