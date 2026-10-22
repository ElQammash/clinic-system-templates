@php use App\Support\Branding; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ isset($title) ? __($title).' · ' : '' }}{{ Branding::name() }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ Branding::styleTag() }}
</head>
{{-- Tablet-First sign-in: indigo→purple screen, a big white card with large fields, sized for a desk tablet. --}}
<body class="min-h-screen bg-gradient-to-br from-primary-700 via-primary-600 to-secondary-700">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-8 flex items-center gap-4 text-white">
            <x-clinic-logo size="size-16" class="ring-4 ring-white/30" />
            <div>
                <p class="text-3xl font-extrabold">{{ Branding::name() }}</p>
                <p class="text-lg text-white/80">{{ __('Reception desk') }}</p>
            </div>
        </div>

        <main class="w-full max-w-lg rounded-[2rem] bg-white p-8 shadow-(--shadow-lift) sm:p-10">
            {{ $slot }}
        </main>

        <div class="mt-8 flex gap-3">
            @foreach (config('clinic.locales') as $code => $locale)
                @continue($code === app()->getLocale())
                <form method="post" action="{{ route('locale.switch', $code) }}">
                    @csrf
                    <button type="submit" class="min-h-12 rounded-2xl bg-white/15 px-6 text-lg font-bold text-white ring-2 ring-white/30 hover:bg-white/25">{{ $locale['name'] }}</button>
                </form>
            @endforeach
        </div>
    </div>
</body>
</html>
