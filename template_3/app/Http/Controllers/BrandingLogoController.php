<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the clinic logo from private storage (no storage:link needed on shared hosting).
 */
class BrandingLogoController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        abort_unless(Branding::hasLogo(), 404);

        return Storage::disk(config('clinic.logo.disk'))->response(settings('logo_path'), null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
