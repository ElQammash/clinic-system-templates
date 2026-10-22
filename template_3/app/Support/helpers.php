<?php

use App\Services\Settings;
use App\Support\Format;

if (! function_exists('settings')) {
    /**
     * settings() returns the service, settings('key', default) returns a value.
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|null $amount, bool $withSymbol = true): string
    {
        return Format::money($amount, $withSymbol);
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return config('clinic.locales.'.app()->getLocale().'.dir') === 'rtl';
    }
}
