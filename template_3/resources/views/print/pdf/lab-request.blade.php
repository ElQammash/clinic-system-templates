@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $labRequest->patient;
    $primary = Branding::primaryColor() ?? '#f25c49';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 10pt; color: #3b2a24; }
        .muted { color: #8a7468; }
        .rule { height: 4px; border-radius: 4px; background: {{ $primary }}; margin: 6px 0 10px; }
        .item { border-bottom: 1px dashed #f0e2d2; padding: 5px 0; font-weight: bold; }
    </style>
</head>
<body>
    <span style="font-size: 14pt; font-weight: bold; color: {{ $primary }}">{{ Branding::name() }}</span> — {{ $labRequest->doctor->displayName() }}
    <div class="rule"></div>
    <h3 style="text-align: center">{{ __('Lab & imaging request') }}</h3>
    <p><span class="muted">{{ __('Patient') }}:</span> <b>{{ $patient->name }}</b> · {{ $patient->age() }} · <span class="muted">{{ __('Date') }}:</span> {{ Format::date($labRequest->requested_at) }}</p>
    @foreach ($labRequest->items as $item)
        <div class="item">{{ $loop->iteration }}. {{ $item->name }}</div>
    @endforeach
    @if ($labRequest->notes)<p>{{ $labRequest->notes }}</p>@endif
</body>
</html>
