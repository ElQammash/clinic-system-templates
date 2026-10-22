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
{{-- Luxury Minimal sign-in: black editorial panel with a large serif statement, white form side with underlined fields. --}}
<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">
        <div class="relative hidden flex-col justify-between bg-ink p-16 text-white lg:flex">
            <div class="flex items-center gap-4">
                @if (Branding::logoUrl())<img src="{{ Branding::logoUrl() }}" alt="" class="size-12 bg-white object-contain p-1">@else<span class="grid size-12 place-items-center border border-primary-400 font-serif text-lg text-primary-300">{{ Branding::initials() }}</span>@endif
                <span class="font-serif text-xl">{{ Branding::name() }}</span>
            </div>

            <div class="max-w-lg">
                <p class="eyebrow text-primary-400">{{ Branding::tagline() ?? __('specialties.'.settings('specialty')) }}</p>
                <h2 class="mt-6 font-serif text-5xl leading-[1.2]">{{ __('Care, refined.') }}</h2>
                <div class="mt-8 h-px w-16 bg-primary-400"></div>
                <p class="mt-8 max-w-sm text-[15px] leading-relaxed font-light text-white/60">{{ __('Appointments, patient records, prescriptions and billing in one simple place.') }}</p>
            </div>

            <p class="eyebrow text-white/40">© {{ now()->year }}</p>
        </div>

        <div class="flex flex-col px-8 sm:px-16">
            <div class="flex justify-end py-10">
                @foreach (config('clinic.locales') as $code => $locale)
                    @continue($code === app()->getLocale())
                    <form method="post" action="{{ route('locale.switch', $code) }}">
                        @csrf
                        <button type="submit" class="eyebrow text-neutral-500 hover:text-ink">{{ $locale['name'] }}</button>
                    </form>
                @endforeach
            </div>

            <main class="flex flex-1 items-center pb-24">
                <div class="mx-auto w-full max-w-sm">
                    <div class="mb-12 flex items-center gap-4 lg:hidden">
                        <x-clinic-logo size="size-11" />
                        <span class="font-serif text-xl">{{ Branding::name() }}</span>
                    </div>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
