@php use App\Support\Branding; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? __($title).' · ' : '' }}{{ Branding::name() }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ Branding::styleTag() }}
</head>
{{-- Warm & Friendly sign-in: cream canvas, soft coral/orange blobs, floating emoji, one big rounded card. --}}
<body class="relative min-h-screen overflow-hidden">
    <div class="pointer-events-none absolute -top-32 -start-32 size-[30rem] rounded-full bg-primary-200/50 blur-3xl"></div>
    <div class="pointer-events-none absolute -end-40 -bottom-40 size-[34rem] rounded-full bg-secondary-100 blur-3xl"></div>
    <div class="pointer-events-none absolute inset-0 hidden md:block" aria-hidden="true">
        <span class="emoji absolute start-[12%] top-[18%] rotate-[-12deg] text-5xl opacity-80">🩺</span>
        <span class="emoji absolute end-[14%] top-[14%] rotate-[10deg] text-5xl opacity-80">💊</span>
        <span class="emoji absolute start-[18%] bottom-[16%] rotate-[8deg] text-5xl opacity-80">❤️</span>
        <span class="emoji absolute end-[16%] bottom-[20%] rotate-[-8deg] text-5xl opacity-80">📅</span>
    </div>

    <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex flex-col items-center gap-3 text-center">
            <x-clinic-logo size="size-16" class="text-xl" />
            <div>
                <p class="text-2xl font-extrabold text-ink">{{ Branding::name() }}</p>
                @if (Branding::tagline())<p class="text-[15px] text-muted">{{ Branding::tagline() }}</p>@endif
            </div>
        </div>

        <main class="w-full max-w-[420px] rounded-[2rem] border-2 border-line bg-white p-7 shadow-(--shadow-lift) sm:p-8">
            {{ $slot }}
        </main>

        <div class="mt-6 flex items-center gap-3 text-sm font-semibold text-muted">
            @foreach (config('clinic.locales') as $code => $locale)
                @continue($code === app()->getLocale())
                <form method="post" action="{{ route('locale.switch', $code) }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 rounded-full bg-white px-4 py-1.5 shadow-(--shadow-soft) hover:text-ink"><span class="emoji">🌍</span> {{ $locale['name'] }}</button>
                </form>
            @endforeach
            <span>© {{ now()->year }}</span>
        </div>
    </div>
</body>
</html>
