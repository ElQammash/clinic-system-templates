<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Clinic settings stored in the `settings` table, merged over the defaults
 * from config('clinic.settings') and cached until the next write.
 */
class Settings
{
    public const CACHE_KEY = 'clinic.settings';

    /** @var array<string, mixed>|null */
    protected ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->loaded ??= array_merge(config('clinic.settings', []), $this->stored());
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, array{old: mixed, new: mixed}> the keys that actually changed
     */
    public function set(array $values): array
    {
        $current = $this->all();
        $changes = [];

        foreach ($values as $key => $value) {
            if (($current[$key] ?? null) === $value) {
                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            $changes[$key] = ['old' => $current[$key] ?? null, 'new' => $value];
        }

        if ($changes !== []) {
            $this->flush();
        }

        return $changes;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /** @return array<string, mixed> */
    protected function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            // Database not reachable yet (e.g. before install): fall back to defaults.
            return [];
        }
    }

    /** Localised value: reads "{key}_en" when the UI is English, falling back to the Arabic key. */
    public function localized(string $key, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en') {
            return $this->get($key.'_en') ?? $this->get($key);
        }

        return $this->get($key) ?? $this->get($key.'_en');
    }
}
