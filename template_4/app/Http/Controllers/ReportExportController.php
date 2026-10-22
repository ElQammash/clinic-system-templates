<?php

namespace App\Http\Controllers;

use App\Livewire\Reports\Index as ReportsPage;
use App\Models\Appointment;
use App\Models\Expense;
use App\Models\Payment;
use App\Services\PdfRenderer;
use App\Services\Reports\ReportService;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

/**
 * Excel (.xlsx) and PDF exports of the reports page, for the same period / doctor filter.
 */
class ReportExportController extends Controller
{
    public const SHEETS = ['summary', 'daily', 'doctors', 'services', 'payments', 'expenses', 'appointments'];

    public function excel(Request $request, string $sheet)
    {
        [$report, $financial] = $this->report($request);
        abort_unless(in_array($sheet, self::SHEETS, true), 404);
        abort_if($sheet === 'expenses' && ! $financial, 403);

        $filename = "{$sheet}-{$report->from->toDateString()}-{$report->to->toDateString()}.xlsx";

        // Written to a temp file (the library's direct-to-browser mode calls exit()).
        File::ensureDirectoryExists(storage_path('app/exports'));
        $path = storage_path('app/exports/'.Str::uuid().'.xlsx');
        $writer = SimpleExcelWriter::create($path);

        foreach ($this->rows($sheet, $report, $financial) as $row) {
            $writer->addRow($row);
        }
        $writer->close();

        activity('reports')->causedBy($request->user())->event('exported')->withProperties(['sheet' => $sheet, 'from' => $report->from->toDateString(), 'to' => $report->to->toDateString()])->log('report exported');

        return response()->download($path, $filename)->deleteFileAfterSend();
    }

    public function pdf(Request $request, PdfRenderer $pdf)
    {
        [$report, $financial] = $this->report($request);

        return $pdf->download('print.pdf.report', [
            'title' => __('Report'),
            'report' => $report,
            'summary' => $report->summary(),
            'byDoctor' => $report->revenueByDoctor(),
            'byService' => $report->revenueByService(),
            'byMethod' => $report->revenueByMethod(),
            'financial' => $financial,
        ], "report-{$report->from->toDateString()}-{$report->to->toDateString()}.pdf");
    }

    /** @return array{0: ReportService, 1: bool} */
    protected function report(Request $request): array
    {
        $user = $request->user();
        abort_unless(ReportsPage::canView($user), 403);

        $from = $this->date($request->query('from'), CarbonImmutable::today()->startOfMonth());
        $to = $this->date($request->query('to'), CarbonImmutable::today());
        $doctor = ReportService::scopedDoctorFor($user, (int) $request->query('doctor'));
        $financial = $user->can('reports.view_all') || $user->can('reports.view_financial');

        return [ReportService::for(min($from, $to), max($from, $to), $doctor), $financial];
    }

    protected function date(mixed $value, CarbonImmutable $fallback): CarbonImmutable
    {
        try {
            return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /** @return iterable<array<string, mixed>> */
    protected function rows(string $sheet, ReportService $report, bool $financial): iterable
    {
        $range = [$report->from, $report->to];

        return match ($sheet) {
            'summary' => collect($report->summary())
                ->reject(fn ($v, $k) => ! $financial && in_array($k, ['expenses', 'net'], true))
                ->map(fn ($value, $key) => [__('Indicator') => __('report_metrics.'.$key), __('Value') => $value ?? '—'])
                ->values(),
            'daily' => $report->revenueByDay()->map(fn ($total, $day) => [__('Date') => $day, __('Revenue') => $total])->values(),
            'doctors' => $report->revenueByDoctor()->map(fn ($r) => [__('Doctor') => $r['doctor'], __('Visits') => $r['visits'], __('Revenue') => $r['revenue']]),
            'services' => $report->revenueByService(100)->map(fn ($r) => [__('Service') => $r['name'], __('Quantity') => $r['count'], __('Total') => $r['total']]),
            'payments' => Payment::query()->with('patient', 'invoice', 'receiver')
                ->whereBetween('paid_at', $range)
                ->when($report->doctorId, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('doctor_id', $report->doctorId)))
                ->orderBy('paid_at')->lazy()
                ->map(fn (Payment $p) => [
                    __('Receipt') => $p->number,
                    __('Date') => $p->paid_at->format('Y-m-d H:i'),
                    __('Patient') => $p->patient->name,
                    __('Invoice') => $p->invoice?->number,
                    __('Payment method') => $p->method->label(),
                    __('Amount') => $p->amount,
                    __('Received by') => $p->receiver?->name,
                ]),
            'expenses' => Expense::query()->with('category')->whereBetween('spent_at', [$report->from->toDateString(), $report->to->toDateString()])
                ->orderBy('spent_at')->lazy()
                ->map(fn (Expense $e) => [
                    __('Date') => $e->spent_at->toDateString(),
                    __('Category') => $e->category->name(),
                    __('Description') => $e->description,
                    __('Amount') => $e->amount,
                ]),
            'appointments' => Appointment::query()->with('patient', 'doctor', 'visitType')
                ->whereBetween('starts_at', $range)
                ->when($report->doctorId, fn ($q) => $q->where('doctor_id', $report->doctorId))
                ->orderBy('starts_at')->lazy()
                ->map(fn (Appointment $a) => [
                    __('Date') => $a->starts_at->format('Y-m-d'),
                    __('Time') => Format::time($a->starts_at),
                    __('Patient') => $a->patient->name,
                    __('Doctor') => $a->doctor->displayName(),
                    __('Visit type') => $a->visitType?->name(),
                    __('Status') => $a->status->label(),
                ]),
        };
    }
}
