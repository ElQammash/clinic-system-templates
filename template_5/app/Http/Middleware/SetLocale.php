<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale priority: logged-in user's preference → session (guests) → clinic default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('clinic.locales'));

        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? settings('default_locale', config('app.locale'));

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
