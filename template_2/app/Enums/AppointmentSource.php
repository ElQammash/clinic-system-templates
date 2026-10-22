<?php

namespace App\Enums;

enum AppointmentSource: string
{
    case Booked = 'booked';
    case Phone = 'phone';
    case WalkIn = 'walk_in';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Booked => __('At reception'),
            self::Phone => __('By phone'),
            self::WalkIn => __('Walk-in'),
            self::FollowUp => __('Follow-up'),
        };
    }

    /** @return array<string, string> */
    public static function bookableOptions(): array
    {
        return collect([self::Booked, self::Phone, self::FollowUp])->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
