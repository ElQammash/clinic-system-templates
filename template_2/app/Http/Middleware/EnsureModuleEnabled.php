<?php

namespace App\Http\Middleware;

use App\Enums\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('module:lab'). Disabled modules behave as if they don't exist.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(Module::from($module)->enabled(), 404);

        return $next($request);
    }
}
