@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $labRequest->patient;
    $primary = Branding::primaryColor() ?? '#4f46e5';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 11.5pt; color: #020617; }
        .muted { color: #475569; }
        .rule { height: 10px; border-radius: 6px; background: {{ $primary }}; margin: 6px 0 10px; }
        .item { border-bottom: 1px dashed #cbd5e1; padding: 5px 0; font-weight: bold; }
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
