@php use App\Support\Branding; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? __($title).' · ' : '' }}{{ Branding::name() }}</title>
    <script>
        try { if (localStorage.getItem('theme') === 'light') document.documentElement.classList.remove('dark'); } catch (e) {}
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ Branding::styleTag() }}
</head>
{{-- Dark Pro sign-in: centered terminal-like card on a dotted grid with an emerald glow. --}}
<body class="relative min-h-screen overflow-hidden">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(var(--color-line)_1px,transparent_1px)] [background-size:22px_22px] opacity-60"></div>
    <div class="pointer-events-none absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-primary-500/15 blur-3xl"></div>

    <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex items-center gap-3">
            <x-clinic-logo size="size-10" />
            <div>
                <p class="text-base font-semibold text-ink">{{ Branding::name() }}</p>
                @if (Branding::tagline())<p class="text-xs text-muted">{{ Branding::tagline() }}</p>@endif
            </div>
        </div>

        <main class="w-full max-w-[380px] rounded-(--radius-card) border border-line-strong bg-surface/95 p-6 shadow-(--shadow-lift) backdrop-blur">
            {{ $slot }}
        </main>

        <div class="mt-6 flex items-center gap-4 text-xs text-muted">
            @foreach (config('clinic.locales') as $code => $locale)
                @continue($code === app()->getLocale())
                <form method="post" action="{{ route('locale.switch', $code) }}">
                    @csrf
                    <button type="submit" class="hover:text-ink">{{ $locale['name'] }}</button>
                </form>
            @endforeach
            <span class="text-line-strong">·</span>
            <span>© {{ now()->year }}</span>
        </div>
    </div>
</body>
</html>
