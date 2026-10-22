@php use App\Support\Format; @endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 9.5pt; color: #0a0a0a; }
        .cap { font-size: 6.5pt; color: #737373; letter-spacing: {{ is_rtl() ? 0 : 1 }}px; }
        .mono { font-family: dejavusansmono; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid td { border: 1px solid #0a0a0a; padding: 5px 7px; }
    </style>
</head>
<body>
    @include('print.pdf.partials.header', ['heading' => ['label' => __('Payment receipt'), 'value' => $payment->number]])

    <table class="grid">
        <tr>
            <td width="62%"><span class="cap">{{ __('Amount received') }}</span><br><b class="mono" style="font-size: 20pt">{{ money($payment->amount) }}</b></td>
            <td style="background: #0a0a0a; color: #fff"><span class="cap" style="color: #a3a3a3">{{ __('Method') }}</span><br><b>{{ $payment->method->label() }}</b>@if ($payment->reference)<br><span class="mono" style="font-size: 7.5pt">{{ $payment->reference }}</span>@endif</td>
        </tr>
    </table>
    <table class="grid">
        <tr>
            <td><span class="cap">{{ __('Received from') }}</span><br><b>{{ $payment->patient->name }}</b></td>
            <td><span class="cap">{{ __('Date') }}</span><br><span class="mono">{{ $payment->paid_at->format('Y-m-d H:i') }}</span></td>
        </tr>
        <tr>
            <td><span class="cap">{{ __('For invoice') }}</span><br><b class="mono">{{ $invoice->number }}</b></td>
            <td><span class="cap">{{ __('Remaining on invoice') }}</span><br><b class="mono">{{ money($invoice->balance) }}</b></td>
        </tr>
    </table>
</body>
</html>
