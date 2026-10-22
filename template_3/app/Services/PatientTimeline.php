<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;
use App\Support\TimelineEntry;
use Illuminate\Support\Collection;

/**
 * Chronological history of a patient (visits, prescriptions, invoices, attachments…).
 * Each module registers a provider with PatientTimeline::extend() in its service provider,
 * so this class never needs to know about every module.
 */
class PatientTimeline
{
    /** @var array<string, callable(Patient, User): iterable<TimelineEntry>> */
    protected static array $providers = [];

    /** Registering the same key again replaces the provider (safe across app reboots). */
    public static function extend(string $key, callable $provider): void
    {
        static::$providers[$key] = $provider;
    }

    /** @return Collection<int, TimelineEntry> newest first */
    public function for(Patient $patient, User $user, ?string $type = null): Collection
    {
        $entries = collect([
            new TimelineEntry(
                type: 'registered',
                date: $patient->created_at,
                title: __('Patient file opened'),
                subtitle: $patient->creator ? __('by :name', ['name' => $patient->creator->name]) : null,
                icon: 'patients',
                color: 'slate',
            ),
        ]);

        $entries = $entries->merge($this->attachments($patient));

        foreach (static::$providers as $provider) {
            $entries = $entries->merge($provider($patient, $user));
        }

        return $entries
            ->when($type, fn (Collection $c) => $c->where('type', $type))
            ->sortByDesc(fn (TimelineEntry $e) => $e->date->getTimestamp())
            ->values();
    }

    /** @return Collection<int, TimelineEntry> */
    protected function attachments(Patient $patient): Collection
    {
        return $patient->attachments()->get()->map(fn (PatientAttachment $a) => new TimelineEntry(
            type: 'attachment',
            date: $a->taken_at ?? $a->created_at,
            title: $a->title,
            subtitle: $a->category->label(),
            icon: $a->category->icon(),
            color: $a->category->color(),
        ));
    }
}
