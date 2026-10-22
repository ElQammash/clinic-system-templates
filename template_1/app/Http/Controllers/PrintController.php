<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\Payment;
use App\Models\Prescription;
use App\Services\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Printable documents. Default: an HTML page styled for printing (best Arabic
 * rendering, opens the print dialog). ?format=pdf returns a PDF for sharing.
 */
class PrintController extends Controller
{
    public function prescription(Request $request, Prescription $prescription, PdfRenderer $pdf)
    {
        Gate::authorize('view', $prescription);

        $prescription->load(['items', 'patient', 'doctor.doctorProfile', 'visit']);

        activity('medical')->causedBy($request->user())->performedOn($prescription)->event('printed')->log('prescription printed');

        $data = [
            'prescription' => $prescription,
            'title' => __('Prescription').' - '.$prescription->patient->name,
        ];

        return $request->query('format') === 'pdf'
            ? $pdf->download('print.pdf.prescription', $data, "prescription-{$prescription->id}.pdf", 'A5')
            : view('print.prescription', $data);
    }

    public function invoice(Request $request, Invoice $invoice, PdfRenderer $pdf)
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['items', 'payments', 'patient', 'doctor']);
        $data = ['invoice' => $invoice, 'title' => __('Invoice').' '.$invoice->number];

        return $request->query('format') === 'pdf'
            ? $pdf->download('print.pdf.invoice', $data, "{$invoice->number}.pdf", 'A4')
            : view('print.invoice', $data);
    }

    public function receipt(Request $request, Payment $payment, PdfRenderer $pdf)
    {
        $invoice = Invoice::withTrashed()->findOrFail($payment->invoice_id);
        Gate::authorize('view', $invoice);

        $payment->load('receiver', 'patient');
        $data = ['payment' => $payment, 'invoice' => $invoice, 'title' => __('Receipt').' '.$payment->number];

        return $request->query('format') === 'pdf'
            ? $pdf->download('print.pdf.receipt', $data, "{$payment->number}.pdf", 'A5')
            : view('print.receipt', $data);
    }

    public function labRequest(Request $request, LabRequest $labRequest, PdfRenderer $pdf)
    {
        Gate::authorize('view', $labRequest);

        $labRequest->load(['items', 'patient', 'doctor.doctorProfile']);

        $data = [
            'labRequest' => $labRequest,
            'title' => __('Lab request').' - '.$labRequest->patient->name,
        ];

        return $request->query('format') === 'pdf'
            ? $pdf->download('print.pdf.lab-request', $data, "lab-request-{$labRequest->id}.pdf", 'A5')
            : view('print.lab-request', $data);
    }
}
