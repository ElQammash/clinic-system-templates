@php
    use App\Support\Branding;
    use App\Support\Format;
    $primary = Branding::primaryColor() ?? '#b08d57';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 10pt; color: #0a0a0a; }
        .muted { color: #7a7a7a; }
        h2 { color: {{ $primary }}; font-size: 12pt; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #faf7f1; padding: 5px; }
        td { border-bottom: 1px solid #e7e3dc; padding: 5px; }
    </style>
</head>
<body>
    <span style="font-family: {{ is_rtl() ? 'dejavusans' : 'dejavuserif' }}; font-size: 17pt; color: #0a0a0a">{{ Branding::name() }}</span><br>
    <b>{{ __('Report') }}</b> <span class="muted">{{ Format::date($report->from) }} — {{ Format::date($report->to) }}</span>

    <h2>{{ __('Summary') }}</h2>
    <table>
        @foreach ($summary as $key => $value)
            @continue(! $financial && in_array($key, ['expenses', 'net'], true))
            @continue($value === null)
            <tr><td>{{ __('report_metrics.'.$key) }}</td><td><b>{{ in_array($key, ['revenue', 'invoiced', 'discounts', 'outstanding', 'expenses', 'net'], true) ? money($value) : Format::number($value, is_float($value) ? 1 : 0) }}</b></td></tr>
        @endforeach
    </table>

    <h2>{{ __('Revenue by doctor') }}</h2>
    <table>
        <tr><th>{{ __('Doctor') }}</th><th>{{ __('Visits') }}</th><th>{{ __('Revenue') }}</th></tr>
        @foreach ($byDoctor as $row)<tr><td>{{ $row['doctor'] }}</td><td>{{ $row['visits'] }}</td><td>{{ money($row['revenue']) }}</td></tr>@endforeach
    </table>

    <h2>{{ __('Revenue by service') }}</h2>
    <table>
        <tr><th>{{ __('Service') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Total') }}</th></tr>
        @foreach ($byService as $row)<tr><td>{{ $row['name'] }}</td><td>{{ $row['count'] }}</td><td>{{ money($row['total']) }}</td></tr>@endforeach
    </table>
</body>
</html>
