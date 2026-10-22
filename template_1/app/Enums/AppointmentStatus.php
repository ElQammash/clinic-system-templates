<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case Confirmed = 'confirmed';
    case Arrived = 'arrived';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Booked => __('Booked'),
            self::Confirmed => __('Confirmed'),
            self::Arrived => __('Waiting'),
            self::InProgress => __('With doctor'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
            self::NoShow => __('No-show'),
        };
    }

    /** Label for the action that moves an appointment INTO this status. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Booked => __('Reinstate'),
            self::Confirmed => __('Confirm'),
            self::Arrived => __('Check in'),
            self::InProgress => __('Call in'),
            self::Completed => __('Complete'),
            self::Cancelled => __('Cancel appointment'),
            self::NoShow => __('Mark no-show'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Booked => 'slate',
            self::Confirmed => 'blue',
            self::Arrived => 'amber',
            self::InProgress => 'violet',
            self::Completed => 'emerald',
            self::Cancelled => 'rose',
            self::NoShow => 'orange',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Booked => [self::Confirmed, self::Arrived, self::Cancelled, self::NoShow],
            self::Confirmed => [self::Arrived, self::Cancelled, self::NoShow],
            self::Arrived => [self::InProgress, self::Cancelled, self::NoShow],
            self::InProgress => [self::Completed, self::Arrived],
            self::Completed => [],
            self::Cancelled => [self::Booked],
            self::NoShow => [self::Arrived, self::Booked],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /** Statuses that occupy the doctor's time slot. */
    public function blocksSlot(): bool
    {
        return in_array($this, self::active(), true);
    }

    /** @return list<self> */
    public static function active(): array
    {
        return [self::Booked, self::Confirmed, self::Arrived, self::InProgress];
    }

    /** @return list<string> */
    public static function activeValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::active());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
