<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Money is stored as integer minor units (piastres) and exposed as a
 * decimal number of major units (pounds), e.g. DB 15050 <-> 150.5.
 * Services that calculate totals work on the raw minor units (getRawOriginal).
 *
 * @implements CastsAttributes<float, float|int|string|null>
 */
class Money implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        return $value === null ? null : round(((int) $value) / 100, 2);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::toMinor($value);
    }

    public static function toMinor(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
