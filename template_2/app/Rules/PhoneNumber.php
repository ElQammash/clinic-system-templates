<?php

namespace App\Rules;

use App\Support\Format;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a phone number using the clinic "phone_format" setting
 * (egypt: 01xxxxxxxxx by default, or any international number).
 */
class PhoneNumber implements ValidationRule
{
    public function __construct(protected ?string $format = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $format = $this->format ?? settings('phone_format', 'egypt');
        $pattern = config("clinic.phone_patterns.{$format}") ?? config('clinic.phone_patterns.any');
        $normalized = preg_replace('/[\s\-()]/', '', (string) Format::normalizeDigits((string) $value));

        if (! preg_match($pattern, $normalized)) {
            $fail($format === 'egypt'
                ? __('The :attribute must be a valid Egyptian mobile number (01xxxxxxxxx).')
                : __('The :attribute must be a valid phone number.'));
        }
    }

    /** Strip spaces/dashes and convert Arabic-Indic digits before saving. */
    public static function normalize(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return preg_replace('/[\s\-()]/', '', Format::normalizeDigits($value));
    }
}
