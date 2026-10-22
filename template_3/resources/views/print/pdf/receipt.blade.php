@php
    use App\Support\Branding;
    use App\Support\Format;
    $primary = Branding::primaryColor() ?? '#f25c49';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 10pt; color: #3b2a24; text-align: center; }
        .muted { color: #8a7468; }
        .amount { font-size: 22pt; font-weight: bold; color: {{ $primary }}; }
        .box { border: 2px dashed #ffccc3; border-radius: 18px; padding: 12px; margin: 12px 0; }
    </style>
</head>
<body>
    <b style="font-size: 13pt; color: {{ $primary }}">{{ Branding::name() }}</b><br>
    <h3>{{ __('Payment receipt') }}</h3>
    <span class="muted">{{ $payment->number }}</span>
    <div class="box">
        <span class="muted">{{ __('Amount received') }}</span><br>
        <span class="amount">{{ money($payment->amount) }}</span><br>
        {{ $payment->method->label() }} @if ($payment->reference)· {{ $payment->reference }}@endif
    </div>
    <p>{{ __('Received from') }}: <b>{{ $payment->patient->name }}</b></p>
    <p class="muted">{{ Format::dateTime($payment->paid_at) }} · {{ __('For invoice') }} {{ $invoice->number }}</p>
    <p>{{ __('Remaining on invoice') }}: <b>{{ money($invoice->balance) }}</b></p>
</body>
</html>
