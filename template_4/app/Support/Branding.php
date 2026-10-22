<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * Clinic identity for layouts and print templates (name, logo, colors).
 * Template-agnostic: every template reads branding through this class.
 */
class Branding
{
    public static function name(): string
    {
        return (string) settings()->localized('clinic_name');
    }

    public static function tagline(): ?string
    {
        return settings()->localized('clinic_tagline');
    }

    public static function address(): ?string
    {
        return settings()->localized('address');
    }

    public static function hasLogo(): bool
    {
        $path = settings('logo_path');

        return filled($path) && Storage::disk(config('clinic.logo.disk'))->exists($path);
    }

    public static function logoUrl(): ?string
    {
        if (! self::hasLogo()) {
            return null;
        }

        // Version query string busts the browser cache when the logo changes.
        return route('branding.logo', ['v' => substr(md5((string) settings('logo_path')), 0, 8)]);
    }

    /** Logo as a data URI, for PDFs rendered without HTTP access. */
    public static function logoDataUri(): ?string
    {
        if (! self::hasLogo()) {
            return null;
        }

        $disk = Storage::disk(config('clinic.logo.disk'));
        $path = settings('logo_path');

        return 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path));
    }

    public static function initials(): string
    {
        return collect(preg_split('/\s+/u', trim(self::name())))
            ->reject(fn ($word) => in_array($word, ['عيادة', 'مركز', 'Clinic', 'Center', 'د.', 'Dr.'], true))
            ->take(2)
            // Skip the Arabic definite article: "الشفاء" -> "ش"
            ->map(fn ($word) => mb_substr(preg_replace('/^ال(?=\X{2,})/u', '', $word), 0, 1))
            ->implode(' ') ?: 'C';
    }

    public static function primaryColor(): ?string
    {
        $color = settings('primary_color');

        return ColorPalette::isValidHex($color) ? $color : null;
    }

    /** <style> tag overriding the template palette when the clinic picked its own color. */
    public static function styleTag(): HtmlString
    {
        $color = self::primaryColor();

        if ($color === null) {
            return new HtmlString('');
        }

        return new HtmlString('<style id="clinic-brand">:root{'.ColorPalette::cssVariables($color).'}</style>');
    }
}
