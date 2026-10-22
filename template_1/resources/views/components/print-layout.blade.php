@props(['title' => '', 'size' => 'a4'])
@php use App\Support\Branding; @endphp
{{-- Template 1 print shell: on-screen toolbar + paper-sized sheet. Paper styles: resources/css/print.css --}}
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
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur">
        <button type="button" onclick="window.print()" class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">{{ __('Print') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">PDF</a>
        <button type="button" onclick="window.close()" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:text-slate-800">{{ __('Close') }}</button>
    </div>

    <main class="sheet {{ $size }}">
        {{ $slot }}
    </main>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
