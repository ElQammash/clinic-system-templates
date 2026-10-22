@props(['title' => '', 'size' => 'a4'])
@php use App\Support\Branding; @endphp
{{-- Template 4 print shell: minimal toolbar + paper-sized sheet. Paper styles: resources/css/print.css --}}
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
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 border-b border-[#e7e3dc] bg-white px-4 py-3">
        <button type="button" onclick="window.print()" class="bg-[#0a0a0a] px-6 py-2 text-xs tracking-widest text-white uppercase hover:bg-[#7d5f39]">{{ __('Print') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="border border-[#0a0a0a] px-6 py-2 text-xs tracking-widest text-[#0a0a0a] uppercase">PDF</a>
        <button type="button" onclick="window.close()" class="px-4 py-2 text-xs tracking-widest text-neutral-500 uppercase">{{ __('Close') }}</button>
    </div>

    <main class="sheet {{ $size }}">
        {{ $slot }}
    </main>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
