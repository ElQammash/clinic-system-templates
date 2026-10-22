<?php

use App\Rules\PhoneNumber;
use App\Support\ArabicText;
use App\Support\ColorPalette;
use App\Support\Format;
use Illuminate\Support\Facades\Validator;

it('builds a full shade scale from a brand color', function () {
    $shades = ColorPalette::shades('#0d9488');

    expect($shades)->toHaveKeys([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950])
        ->and($shades[600])->toBe('#0d9488')
        ->and($shades[50])->not->toBe($shades[950]);
});

it('rejects invalid colors', function () {
    ColorPalette::shades('teal');
})->throws(InvalidArgumentException::class);

it('validates Egyptian mobile numbers', function (string $phone, bool $valid) {
    $validator = Validator::make(['phone' => $phone], ['phone' => [new PhoneNumber('egypt')]]);

    expect($validator->passes())->toBe($valid);
})->with([
    ['01012345678', true],
    ['01112345678', true],
    ['01212345678', true],
    ['01512345678', true],
    ['010 1234 5678', true],
    ['٠١٠١٢٣٤٥٦٧٨', true],
    ['01312345678', false],
    ['0101234567', false],
    ['+201012345678', false],
    ['abc', false],
]);

it('normalises Arabic text for searching', function () {
    expect(ArabicText::normalize('أحمد'))->toBe('احمد')
        ->and(ArabicText::normalize('إيمان'))->toBe('ايمان')
        ->and(ArabicText::normalize('فاطمة'))->toBe('فاطمه')
        ->and(ArabicText::normalize('مصطفى'))->toBe('مصطفي')
        ->and(ArabicText::normalize('مُحَمَّد  ـعلي'))->toBe('محمد علي');
});

it('converts local Egyptian numbers to international format for WhatsApp', function () {
    expect(Format::internationalPhone('01012345678'))->toBe('201012345678')
        ->and(Format::internationalPhone('٠١٠١٢٣٤٥٦٧٨'))->toBe('201012345678')
        ->and(Format::internationalPhone('00201012345678'))->toBe('201012345678');
});
