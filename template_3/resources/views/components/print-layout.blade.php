@props(['title' => '', 'size' => 'a4'])
@php use App\Support\Branding; @endphp
{{-- Template 3 print shell: cream toolbar + paper-sized sheet. Paper styles: resources/css/print.css --}}
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
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 border-b-2 border-[#f0e2d2] bg-[#fdf7ef]/95 px-4 py-3 backdrop-blur">
        <button type="button" onclick="window.print()" class="rounded-full bg-primary-500 px-6 py-2 text-sm font-bold text-white shadow-[0_3px_0_var(--color-primary-700)]">{{ __('Print') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="rounded-full border-2 border-[#f0e2d2] bg-white px-6 py-2 text-sm font-bold text-[#3b2a24]">PDF</a>
        <button type="button" onclick="window.close()" class="rounded-full px-4 py-2 text-sm font-bold text-[#8a7468]">{{ __('Close') }}</button>
    </div>

    <main class="sheet {{ $size }}">
        {{ $slot }}
    </main>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
