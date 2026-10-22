@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $invoice->patient;
    $primary = Branding::primaryColor() ?? '#b08d57';
    $end = is_rtl() ? 'left' : 'right';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 10pt; color: #0a0a0a; }
        .muted { color: #7a7a7a; }
        .rule { height: 1px; background: {{ $primary }}; margin: 6px 0 14px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.items th { border-bottom: 1px solid #0a0a0a; color: #7a7a7a; font-size: 8pt; padding: 6px; }
        table.items td { border-bottom: 1px solid #e7e3dc; padding: 6px; }
        .totals td { padding: 3px 6px; }
        .footer { border-top: 1px solid #e7e3dc; margin-top: 24px; padding-top: 6px; text-align: center; font-size: 8pt; color: #7a7a7a; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td width="60%">
                @if (settings('print_show_logo') && Branding::logoDataUri())<img src="{{ Branding::logoDataUri() }}" height="44"><br>@endif
                <span style="font-family: {{ is_rtl() ? 'dejavusans' : 'dejavuserif' }}; font-size: 17pt; color: #0a0a0a">{{ Branding::name() }}</span><br>
                <span class="muted">{{ Branding::tagline() }}</span>
            </td>
            <td width="40%" style="text-align: {{ $end }}">
                <span style="font-family: {{ is_rtl() ? 'dejavusans' : 'dejavuserif' }}; font-size: 18pt">{{ __('Invoice') }}</span><br>
                <span class="muted">{{ $invoice->number }}</span>
                @if ($invoice->isVoid())<br><b style="color: #e11d48">{{ __('Void') }}</b>@endif
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table width="100%">
        <tr>
            <td><span class="muted">{{ __('Patient') }}:</span> <b>{{ $patient->name }}</b> ({{ $patient->file_number }})</td>
            <td style="text-align: {{ $end }}"><span class="muted">{{ __('Date') }}:</span> {{ Format::date($invoice->issued_at) }}</td>
        </tr>
        @if ($invoice->doctor)
            <tr><td colspan="2"><span class="muted">{{ __('Doctor') }}:</span> {{ $invoice->doctor->displayName() }}</td></tr>
        @endif
    </table>

    <table class="items">
        <thead><tr><th>{{ __('Description') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Price') }}</th><th>{{ __('Total') }}</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center">{{ $item->quantity }}</td>
                    <td>{{ money($item->unit_price) }}</td>
                    <td><b>{{ money($item->total) }}</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" width="45%" style="margin-top: 10px; margin-{{ is_rtl() ? 'right' : 'left' }}: 55%">
        <tr><td class="muted">{{ __('Subtotal') }}</td><td>{{ money($invoice->subtotal) }}</td></tr>
        @if ($invoice->discount_amount > 0)<tr><td class="muted">{{ __('Discount') }}</td><td>− {{ money($invoice->discount_amount) }}</td></tr>@endif
        <tr><td><b>{{ __('Total') }}</b></td><td><b>{{ money($invoice->total) }}</b></td></tr>
        <tr><td class="muted">{{ __('Paid') }}</td><td>{{ money($invoice->paid_amount) }}</td></tr>
        <tr><td><b>{{ __('Remaining') }}</b></td><td><b>{{ money($invoice->balance) }}</b></td></tr>
    </table>

    <div class="footer">
        @if (settings()->localized('invoice_footer')){{ settings()->localized('invoice_footer') }}<br>@endif
        {{ Branding::address() }} @if (settings('phone')) · {{ settings('phone') }} @endif
    </div>
</body>
</html>
