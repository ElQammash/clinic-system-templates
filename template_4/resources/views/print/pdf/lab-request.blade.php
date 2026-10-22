@php
    use App\Support\Branding;
    use App\Support\Format;
    $patient = $labRequest->patient;
    $primary = Branding::primaryColor() ?? '#b08d57';
@endphp
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <style>
        body { font-family: dejavusans; font-size: 10pt; color: #0a0a0a; }
        .muted { color: #7a7a7a; }
        .rule { height: 1px; background: {{ $primary }}; margin: 6px 0 10px; }
        .item { border-bottom: 1px dashed #e7e3dc; padding: 5px 0; font-weight: bold; }
    </style>
</head>
<body>
    <span style="font-family: {{ is_rtl() ? 'dejavusans' : 'dejavuserif' }}; font-size: 17pt; color: #0a0a0a">{{ Branding::name() }}</span> — {{ $labRequest->doctor->displayName() }}
    <div class="rule"></div>
    <h3 style="text-align: center">{{ __('Lab & imaging request') }}</h3>
    <p><span class="muted">{{ __('Patient') }}:</span> <b>{{ $patient->name }}</b> · {{ $patient->age() }} · <span class="muted">{{ __('Date') }}:</span> {{ Format::date($labRequest->requested_at) }}</p>
    @foreach ($labRequest->items as $item)
        <div class="item">{{ $loop->iteration }}. {{ $item->name }}</div>
    @endforeach
    @if ($labRequest->notes)<p>{{ $labRequest->notes }}</p>@endif
</body>
</html>
