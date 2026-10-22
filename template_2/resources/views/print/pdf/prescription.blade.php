@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
    $primary = Branding::primaryColor() ?? '#10b981';
@endphp
{{-- mPDF version (simple tables + inline CSS: mPDF does not support Tailwind). --}}
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 9.5pt; color: #0a0a0a; }
        .cap { font-size: 6.5pt; color: #737373; letter-spacing: {{ is_rtl() ? 0 : 1 }}px; }
        .mono { font-family: dejavusansmono; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid td { border: 1px solid #0a0a0a; padding: 4px 6px; vertical-align: top; }
        .file { background: #0a0a0a; color: #fff; text-align: center; width: 22%; }
        .item td { border-bottom: 1px solid #d4d4d4; padding: 5px 0; vertical-align: top; }
        .footer { border-top: 2px solid #0a0a0a; margin-top: 18px; padding-top: 4px; font-size: 7.5pt; color: #525252; }
    </style>
</head>
<body>
    @include('print.pdf.partials.header', ['doctor' => $prescription->doctor])

    <table class="grid">
        <tr>
            <td colspan="2"><span class="cap">{{ __('Patient') }}</span><br><b>{{ $patient->name }}</b></td>
            <td><span class="cap">{{ __('Date') }}</span><br><b class="mono">{{ $prescription->issued_at->format('Y-m-d') }}</b></td>
            <td rowspan="2" class="file"><span class="cap" style="color: #a3a3a3">{{ __('File no.') }}</span><br><b class="mono" style="font-size: 14pt">{{ $patient->file_number }}</b></td>
        </tr>
        <tr>
            <td><span class="cap">{{ __('Age') }}</span><br>{{ $patient->age() ?: '—' }}</td>
            <td><span class="cap">{{ __('Gender') }}</span><br>{{ $patient->gender?->label() ?? '—' }}</td>
            <td><span class="cap">{{ __('Weight') }}</span><br><span class="mono">{{ $visit?->weight ? Format::number($visit->weight, 1).' kg' : '—' }}</span></td>
        </tr>
    </table>

    @if ($visit?->diagnosis)
        <p><span class="cap">{{ __('Diagnosis') }}</span> <b>{{ $visit->diagnosis }}</b></p>
    @endif

    <p style="margin: 12px 0 0; border-bottom: 2px solid #0a0a0a; padding-bottom: 2px"><b class="mono" style="font-size: 15pt">Rx</b></p>
    <table width="100%" style="border-collapse: collapse">
        @foreach ($prescription->items as $item)
            <tr class="item">
                <td width="24" class="mono" style="color: {{ $primary }}"><b>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</b></td>
                <td>
                    <b style="font-size: 11pt">{{ $item->medicine_name }}</b><br>
                    {{ $item->directions() }}
                    @if ($item->instructions)<br><span style="color: #737373">{{ $item->instructions }}</span>@endif
                </td>
            </tr>
        @endforeach
    </table>

    @if ($prescription->notes)
        <p style="margin-top: 10px; border-{{ is_rtl() ? 'right' : 'left' }}: 2px solid #0a0a0a; padding: 0 6px">{{ $prescription->notes }}</p>
    @endif
    @if ($visit?->follow_up_date)
        <p><span style="border: 1px solid #0a0a0a; padding: 2px 6px"><b>{{ __('Follow-up') }}: {{ Format::longDate($visit->follow_up_date) }}</b></span></p>
    @endif

    <table width="100%" style="margin-top: 30px">
        <tr><td></td><td width="40%" style="text-align: center; border-top: 1px solid #0a0a0a; padding-top: 3px" class="cap">{{ __('Doctor\'s signature') }}</td></tr>
    </table>

    <div class="footer">
        @if (settings()->localized('prescription_footer')){{ settings()->localized('prescription_footer') }}<br>@endif
        {{ Branding::address() }} @if (settings('phone')) · <span class="mono">{{ settings('phone') }}</span> @endif
    </div>
</body>
</html>
