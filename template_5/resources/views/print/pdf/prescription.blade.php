@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
    $doctor = $prescription->doctor;
    $primary = Branding::primaryColor() ?? '#4f46e5';
@endphp
{{-- mPDF version (simple tables + inline CSS: mPDF does not support Tailwind). --}}
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 11.5pt; color: #020617; }
        .muted { color: #475569; }
        .rule { height: 10px; border-radius: 6px; background: {{ $primary }}; margin: 6px 0 10px; }
        .box { background: #eef2ff; padding: 6px 8px; border-radius: 10px; }
        .rx { font-size: 26pt; color: {{ $primary }}; font-weight: bold; font-style: italic; }
        .item { border-bottom: 1px dashed #cbd5e1; padding: 5px 0; }
        .med { font-size: 14pt; font-weight: bold; }
        .footer { border-top: 1px solid #c7d2fe; margin-top: 18px; padding-top: 6px; text-align: center; font-size: 8pt; color: #475569; }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td width="60%">
                @if (settings('print_show_logo') && Branding::logoDataUri())
                    <img src="{{ Branding::logoDataUri() }}" height="44"><br>
                @endif
                <span style="font-size: 14pt; font-weight: bold; color: {{ $primary }}">{{ Branding::name() }}</span><br>
                <span class="muted">{{ Branding::tagline() }}</span>
            </td>
            <td width="40%" style="text-align: {{ is_rtl() ? 'left' : 'right' }}">
                <b>{{ $doctor->displayName() }}</b><br>
                <span class="muted">{{ $doctor->doctorProfile?->title() }}</span>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <div class="box">
        <table width="100%">
            <tr>
                <td><span class="muted">{{ __('Patient') }}:</span> <b>{{ $patient->name }}</b></td>
                <td style="text-align: {{ is_rtl() ? 'left' : 'right' }}"><span class="muted">{{ __('Date') }}:</span> <b>{{ Format::date($prescription->issued_at) }}</b></td>
            </tr>
            <tr>
                <td><span class="muted">{{ __('Age') }}:</span> {{ $patient->age() ?: '—' }} @if ($visit?->weight) · <span class="muted">{{ __('Weight') }}:</span> {{ Format::number($visit->weight, 1) }} kg @endif</td>
                <td style="text-align: {{ is_rtl() ? 'left' : 'right' }}"><span class="muted">{{ __('File no.') }}:</span> {{ $patient->file_number }}</td>
            </tr>
        </table>
    </div>

    <p class="rx">℞</p>
    @foreach ($prescription->items as $item)
        <div class="item">
            <span class="med">{{ $loop->iteration }}. {{ $item->medicine_name }}</span><br>
            {{ $item->directions() }}
            @if ($item->instructions)<br><span class="muted">{{ $item->instructions }}</span>@endif
        </div>
    @endforeach

    @if ($prescription->notes)
        <p style="margin-top: 10px">{{ $prescription->notes }}</p>
    @endif
    @if ($visit?->follow_up_date)
        <p style="color: {{ $primary }}"><b>{{ __('Follow-up') }}: {{ Format::longDate($visit->follow_up_date) }}</b></p>
    @endif

    <table width="100%" style="margin-top: 30px">
        <tr><td></td><td width="40%" style="text-align: center; border-top: 1px solid #94a3b8; padding-top: 4px" class="muted">{{ __('Doctor\'s signature') }}</td></tr>
    </table>

    <div class="footer">
        @if (settings()->localized('prescription_footer')){{ settings()->localized('prescription_footer') }}<br>@endif
        {{ Branding::address() }} @if (settings('phone')) · {{ settings('phone') }} @endif
    </div>
</body>
</html>
