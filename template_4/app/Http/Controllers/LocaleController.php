<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('clinic.locales')), 404);

        $request->user()?->update(['locale' => $locale]);
        $request->session()->put('locale', $locale);

        return redirect()->back();
    }
}
