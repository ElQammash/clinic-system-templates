@php
    $patient = $labRequest->patient;
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 9.5pt; color: #0a0a0a; }
        .cap { font-size: 6.5pt; color: #737373; letter-spacing: {{ is_rtl() ? 0 : 1 }}px; }
        .mono { font-family: dejavusansmono; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid td { border: 1px solid #0a0a0a; padding: 4px 6px; }
        .item { border-bottom: 1px solid #d4d4d4; padding: 5px 0; font-weight: bold; }
    </style>
</head>
<body>
    @include('print.pdf.partials.header', ['doctor' => $labRequest->doctor])
    <p class="cap" style="margin-top: 10px">{{ __('Lab & imaging request') }}</p>
    <table class="grid">
        <tr>
            <td><span class="cap">{{ __('Patient') }}</span><br><b>{{ $patient->name }}</b></td>
            <td><span class="cap">{{ __('Age') }}</span><br>{{ $patient->age() ?: '—' }}</td>
            <td><span class="cap">{{ __('Date') }}</span><br><span class="mono">{{ $labRequest->requested_at->format('Y-m-d') }}</span></td>
            <td style="background: #0a0a0a; color: #fff; text-align: center"><span class="cap" style="color: #a3a3a3">{{ __('File no.') }}</span><br><b class="mono">{{ $patient->file_number }}</b></td>
        </tr>
    </table>
    @foreach ($labRequest->items as $item)
        <div class="item"><span class="mono" style="color: #737373">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>&nbsp;&nbsp;{{ $item->name }}</div>
    @endforeach
    @if ($labRequest->notes)<p>{{ $labRequest->notes }}</p>@endif
</body>
</html>
