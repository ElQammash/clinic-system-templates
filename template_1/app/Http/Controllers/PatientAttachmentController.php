<?php

namespace App\Http\Controllers;

use App\Models\PatientAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams medical files from private storage after an authorisation check.
 * Every access is recorded in the activity log.
 */
class PatientAttachmentController extends Controller
{
    public function __invoke(Request $request, PatientAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        $download = $request->boolean('download');

        activity('medical')
            ->causedBy($request->user())
            ->performedOn($attachment->patient)
            ->event($download ? 'attachment_downloaded' : 'attachment_viewed')
            ->withProperties(['attachment_id' => $attachment->id, 'title' => $attachment->title])
            ->log($download ? 'attachment downloaded' : 'attachment viewed');

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        $headers = [
            'Content-Type' => $attachment->mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // Only images and PDFs are shown inline; everything else is downloaded.
        $inline = ! $download && ($attachment->isImage() || $attachment->isPdf());

        return $inline
            ? $disk->response($attachment->path, $attachment->original_name, $headers)
            : $disk->download($attachment->path, $attachment->original_name, $headers);
    }
}
