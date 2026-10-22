@props(['title' => '', 'size' => 'a4'])
@php use App\Support\Branding; @endphp
{{-- Template 5 print shell: big touch toolbar + paper-sized sheet. Paper styles: resources/css/print.css --}}
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
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 border-b-2 border-[#d9dcea] bg-white px-4 py-3">
        <button type="button" onclick="window.print()" class="min-h-14 rounded-2xl bg-primary-600 px-8 text-lg font-bold text-white">{{ __('Print') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="grid min-h-14 place-items-center rounded-2xl border-2 border-slate-300 bg-white px-8 text-lg font-bold text-slate-900">PDF</a>
        <button type="button" onclick="window.close()" class="min-h-14 rounded-2xl px-6 text-lg font-bold text-slate-700">{{ __('Close') }}</button>
    </div>

    <main class="sheet {{ $size }}">
        {{ $slot }}
    </main>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
