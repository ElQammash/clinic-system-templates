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
<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Brand panel with a light medical illustration --}}
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-secondary-700 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <svg class="absolute -end-24 -top-24 size-[28rem] text-white/10" viewBox="0 0 200 200" fill="currentColor" aria-hidden="true">
                <path d="M80 10h40v70h70v40h-70v70H80v-70H10V80h70z" />
            </svg>
            <svg class="absolute -bottom-32 -start-20 size-[30rem] text-white/5" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="10" aria-hidden="true">
                <circle cx="100" cy="100" r="80" />
            </svg>

            <div class="relative flex items-center gap-3 text-white">
                <x-clinic-logo size="size-12" class="bg-white/15 ring-1 ring-white/30" />
                <div>
                    <p class="text-lg font-bold">{{ Branding::name() }}</p>
                    @if (Branding::tagline())
                        <p class="text-sm text-white/75">{{ Branding::tagline() }}</p>
                    @endif
                </div>
            </div>

            <div class="relative max-w-md text-white">
                <svg class="mb-8 h-24 w-auto text-white/90" viewBox="0 0 160 60" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 34h34l8-18 12 36 10-26 6 8h86" />
                </svg>
                <h2 class="text-3xl leading-snug font-bold">{{ __('Your clinic, organised.') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-white/80">{{ __('Appointments, patient records, prescriptions and billing in one simple place.') }}</p>
            </div>

            <p class="relative text-xs text-white/60">© {{ now()->year }} {{ Branding::name() }}</p>
        </div>

        <div class="flex flex-col">
            <div class="flex justify-end p-4">
                @foreach (config('clinic.locales') as $code => $locale)
                    @continue($code === app()->getLocale())
                    <form method="post" action="{{ route('locale.switch', $code) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost gap-1.5"><x-icon name="language" class="size-5" /> {{ $locale['name'] }}</button>
                    </form>
                @endforeach
            </div>

            <main class="flex flex-1 items-center justify-center px-6 pb-16">
                <div class="w-full max-w-sm">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-clinic-logo size="size-11" />
                        <p class="text-lg font-bold">{{ Branding::name() }}</p>
                    </div>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
