@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $invoice->patient;
    $end = is_rtl() ? 'left' : 'right';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 9.5pt; color: #0a0a0a; }
        .cap { font-size: 6.5pt; color: #737373; letter-spacing: {{ is_rtl() ? 0 : 1 }}px; }
        .mono { font-family: dejavusansmono; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid td { border: 1px solid #0a0a0a; padding: 4px 6px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.items th { background: #0a0a0a; color: #fff; padding: 5px; font-size: 7.5pt; }
        table.items td { border-bottom: 1px solid #d4d4d4; padding: 5px; }
        table.totals { border-collapse: collapse; border: 1px solid #0a0a0a; }
        table.totals td { padding: 3px 8px; }
        .footer { border-top: 2px solid #0a0a0a; margin-top: 24px; padding-top: 4px; font-size: 7.5pt; color: #525252; }
    </style>
</head>
<body>
    @include('print.pdf.partials.header', ['heading' => ['label' => __('Invoice'), 'value' => $invoice->number]])
    @if ($invoice->isVoid())<p style="color: #e11d48; font-weight: bold; letter-spacing: 2px">{{ __('Void') }}</p>@endif

    <table class="grid">
        <tr>
            <td><span class="cap">{{ __('Date') }}</span><br><b class="mono">{{ $invoice->issued_at->format('Y-m-d') }}</b></td>
            <td><span class="cap">{{ __('Patient') }}</span><br><b>{{ $patient->name }}</b></td>
            <td><span class="cap">{{ __('File no.') }}</span><br><b class="mono">{{ $patient->file_number }}</b></td>
            <td><span class="cap">{{ __('Doctor') }}</span><br>{{ $invoice->doctor?->displayName() ?? '—' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th width="24">#</th><th>{{ __('Description') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Price') }}</th><th>{{ __('Total') }}</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td class="mono" style="color: #737373">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="mono" style="text-align: center">{{ $item->quantity }}</td>
                    <td class="mono">{{ money($item->unit_price) }}</td>
                    <td class="mono"><b>{{ money($item->total) }}</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" width="45%" style="margin-top: 10px; margin-{{ is_rtl() ? 'right' : 'left' }}: 55%">
        <tr><td style="color: #525252">{{ __('Subtotal') }}</td><td class="mono">{{ money($invoice->subtotal) }}</td></tr>
        @if ($invoice->discount_amount > 0)<tr><td style="color: #525252">{{ __('Discount') }}</td><td class="mono">− {{ money($invoice->discount_amount) }}</td></tr>@endif
        <tr><td style="border-top: 1px solid #0a0a0a"><b>{{ __('Total') }}</b></td><td class="mono" style="border-top: 1px solid #0a0a0a"><b>{{ money($invoice->total) }}</b></td></tr>
        <tr><td style="color: #525252">{{ __('Paid') }}</td><td class="mono">{{ money($invoice->paid_amount) }}</td></tr>
        <tr style="background: #0a0a0a; color: #fff"><td><b>{{ __('Remaining') }}</b></td><td class="mono"><b>{{ money($invoice->balance) }}</b></td></tr>
    </table>

    <div class="footer">
        @if (settings()->localized('invoice_footer')){{ settings()->localized('invoice_footer') }}<br>@endif
        {{ Branding::address() }} @if (settings('phone')) · <span class="mono">{{ settings('phone') }}</span> @endif
    </div>
</body>
</html>
