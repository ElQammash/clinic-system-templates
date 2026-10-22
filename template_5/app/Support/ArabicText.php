<?php

namespace App\Support;

/**
 * Normalises Arabic text for searching, so "احمد" finds "أحمد",
 * "فاطمه" finds "فاطمة" and "مصطفي" finds "مصطفى".
 */
class ArabicText
{
    protected const MAP = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ة' => 'ه',
        'ى' => 'ي',
        'ؤ' => 'و',
        'ئ' => 'ي',
    ];

    public static function normalize(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        // Remove diacritics (tashkeel) and tatweel.
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, self::MAP);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return mb_strtolower($text);
    }
}
