<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Builds a Tailwind-like 50..950 shade scale from one brand color so the
 * clinic can rebrand at runtime (CSS variables override the template palette).
 * The given color is used as the 600 shade (the usual "button" shade).
 */
class ColorPalette
{
    /** shade => [mix target (white|black), ratio] */
    protected const SHADES = [
        50 => ['white', 0.94],
        100 => ['white', 0.86],
        200 => ['white', 0.72],
        300 => ['white', 0.54],
        400 => ['white', 0.32],
        500 => ['white', 0.14],
        600 => ['white', 0.0],
        700 => ['black', 0.16],
        800 => ['black', 0.32],
        900 => ['black', 0.46],
        950 => ['black', 0.62],
    ];

    public static function isValidHex(?string $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1;
    }

    /** @return array<int, string> shade => hex */
    public static function shades(string $hex): array
    {
        if (! self::isValidHex($hex)) {
            throw new InvalidArgumentException("Invalid color [{$hex}].");
        }

        [$r, $g, $b] = sscanf(strtolower($hex), '#%02x%02x%02x');
        $result = [];

        foreach (self::SHADES as $shade => [$target, $ratio]) {
            $t = $target === 'white' ? 255 : 0;
            $mix = fn (int $c) => (int) round($c + ($t - $c) * $ratio);
            $result[$shade] = sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
        }

        return $result;
    }

    /** CSS declarations "--color-{name}-{shade}: #hex;" ready to drop into :root. */
    public static function cssVariables(string $hex, string $name = 'primary'): string
    {
        return collect(self::shades($hex))
            ->map(fn (string $value, int $shade) => "--color-{$name}-{$shade}: {$value};")
            ->implode(' ');
    }

    /** Best readable text color (black/white) on top of the given background. */
    public static function contrastText(string $hex): string
    {
        [$r, $g, $b] = sscanf(strtolower($hex), '#%02x%02x%02x');
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.6 ? '#0f172a' : '#ffffff';
    }
}
