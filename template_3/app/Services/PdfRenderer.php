<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders Blade views to PDF with mPDF, configured for Arabic (RTL text shaping
 * via the bundled DejaVu Sans font). Browser printing remains the primary path;
 * PDFs are for download / WhatsApp sharing.
 */
class PdfRenderer
{
    /** @param  array<string, mixed>  $data */
    public function render(string $view, array $data, string $format = 'A4'): string
    {
        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);

        $pdf = LaravelMpdf::loadView($view, $data, [], [
            'mode' => 'utf-8',
            'format' => $format,
            'default_font' => 'dejavusans',
            'default_font_size' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 12,
            'directionality' => is_rtl() ? 'rtl' : 'ltr',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useOTL' => 0xFF,
            'temp_dir' => $tempDir,
            'title' => $data['title'] ?? config('app.name'),
        ]);

        return $pdf->output();
    }

    /** @param  array<string, mixed>  $data */
    public function download(string $view, array $data, string $filename, string $format = 'A4'): Response
    {
        return response($this->render($view, $data, $format), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
