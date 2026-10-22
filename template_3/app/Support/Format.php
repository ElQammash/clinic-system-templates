<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Locale-aware formatting for numbers, money, dates and phones.
 * Respects the clinic "digits" setting (western 123 / arabic ١٢٣).
 */
class Format
{
    protected const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public static function digits(string|int|float|null $value): string
    {
        $value = (string) $value;

        if (app()->getLocale() === 'ar' && settings('digits') === 'arabic') {
            return strtr($value, array_combine(range(0, 9), self::ARABIC_DIGITS) + ['.' => '٫', ',' => '٬']);
        }

        return $value;
    }

    /** Convert any Arabic-Indic digits typed by the user into western digits. */
    public static function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr($value, array_flip(self::ARABIC_DIGITS) + ['۰' => 0, '۱' => 1, '۲' => 2, '۳' => 3, '۴' => 4, '۵' => 5, '۶' => 6, '۷' => 7, '۸' => 8, '۹' => 9]);
    }

    public static function number(float|int|null $value, int $decimals = 0): string
    {
        return self::digits(number_format((float) $value, $decimals));
    }

    /** @param  float|int|null  $amount  major units (e.g. 150.5 pounds) */
    public static function money(float|int|null $amount, bool $withSymbol = true): string
    {
        $amount = (float) $amount;
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
        $formatted = self::number($amount, $decimals);

        if (! $withSymbol) {
            return $formatted;
        }

        $symbol = settings()->localized('currency_symbol');

        return app()->getLocale() === 'ar' ? "{$formatted} {$symbol}" : "{$symbol} {$formatted}";
    }

    public static function date(CarbonInterface|string|null $date, string $format = 'j F Y'): string
    {
        if (blank($date)) {
            return '';
        }

        return self::digits(Carbon::parse($date)->locale(app()->getLocale())->translatedFormat($format));
    }

    /** "الثلاثاء ٢٠ أكتوبر ٢٠٢٦" */
    public static function longDate(CarbonInterface|string|null $date): string
    {
        return self::date($date, 'l j F Y');
    }

    public static function time(CarbonInterface|string|null $date): string
    {
        return self::date($date, 'g:i A');
    }

    public static function dateTime(CarbonInterface|string|null $date): string
    {
        return self::date($date, 'j F Y - g:i A');
    }

    /** Age as "5 years", "8 months" or "12 days" for babies. */
    public static function age(CarbonInterface|string|null $birthDate): string
    {
        if (blank($birthDate)) {
            return '';
        }

        $birth = Carbon::parse($birthDate);
        $years = (int) $birth->diffInYears(now());

        if ($years >= 2) {
            return self::digits(trans_choice(':count year|:count years', $years, ['count' => $years]));
        }

        $months = (int) $birth->diffInMonths(now());
        if ($months >= 1) {
            return self::digits(trans_choice(':count month|:count months', $months, ['count' => $months]));
        }

        $days = (int) $birth->diffInDays(now());

        return self::digits(trans_choice(':count day|:count days', $days, ['count' => $days]));
    }

    /** Local number → international without "+" for wa.me links: 01012345678 → 201012345678 */
    public static function internationalPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', self::normalizeDigits($phone));
        $code = config('clinic.country_calling_code');

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            return $code.substr($digits, 1);
        }

        return $digits;
    }
}
