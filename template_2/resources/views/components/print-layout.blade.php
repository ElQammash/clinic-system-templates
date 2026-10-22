@props(['title' => '', 'size' => 'a4'])
@php use App\Support\Branding; @endphp
{{-- Template 2 print shell: on-screen toolbar + paper sheet. Paper styles: resources/css/print.css --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @fonts
    @vite('resources/css/print.css')
    {{ Branding::styleTag() }}
</head>
<body>
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 border-b border-[#233047] bg-[#0b1220] px-4 py-2.5">
        <button type="button" onclick="window.print()" class="rounded-sm bg-[#10b981] px-4 py-1.5 text-sm font-semibold text-[#0b1220]">{{ __('Print') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="rounded-sm border border-[#33425c] px-4 py-1.5 text-sm font-semibold text-[#e7edf6]">PDF</a>
        <button type="button" onclick="window.close()" class="px-3 py-1.5 text-sm text-[#7d8ba1]">{{ __('Close') }}</button>
    </div>

    <main class="sheet {{ $size }}">
        {{ $slot }}
    </main>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
