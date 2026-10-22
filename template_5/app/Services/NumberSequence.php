<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free, race-safe counters for human-readable numbers.
 * Must be called inside the same transaction that saves the numbered record.
 */
class NumberSequence
{
    public function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $row = DB::table('sequences')->where('key', $key)->lockForUpdate()->first();

            if ($row === null) {
                DB::table('sequences')->insertOrIgnore([
                    'key' => $key,
                    'next_value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = DB::table('sequences')->where('key', $key)->lockForUpdate()->first();
            }

            DB::table('sequences')->where('id', $row->id)->update([
                'next_value' => $row->next_value + 1,
                'updated_at' => now(),
            ]);

            return (int) $row->next_value;
        });
    }

    /** e.g. formatted('invoice:2026', 'INV-2026-', 5) => "INV-2026-00042" */
    public function formatted(string $key, string $prefix = '', int $pad = 5): string
    {
        return $prefix.str_pad((string) $this->next($key), $pad, '0', STR_PAD_LEFT);
    }

    /** Make sure the next value is at least $value (used after importing data). */
    public function ensureAtLeast(string $key, int $value): void
    {
        $this->next($key); // creates the row if missing
        DB::table('sequences')->where('key', $key)->where('next_value', '<', $value)->update(['next_value' => $value]);
    }
}
