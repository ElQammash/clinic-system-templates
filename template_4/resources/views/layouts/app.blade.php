@php
    use App\Support\Branding;
    use App\Support\Navigation;

    $user = auth()->user();
    $navigation = Navigation::for($user);
    $pageTitle = isset($title) ? __($title) : null;
    $counter = 0;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ Branding::name() }}</title>
    @if (Branding::logoUrl())
        <link rel="icon" href="{{ Branding::logoUrl() }}">
    @endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ Branding::styleTag() }}
</head>
{{-- Luxury Minimal shell: a quiet, text-only index on the start side and a wide, airy content area. --}}
<body class="min-h-screen" x-data="{ menuOpen: false }" x-on:open-url.window="window.open($event.detail.url ?? $event.detail[0]?.url, '_blank', 'noopener')">
    <div x-show="menuOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-white/80 backdrop-blur-sm lg:hidden" x-on:click="menuOpen = false"></div>

    <aside
        class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col border-e border-line bg-white px-10 transition-transform duration-300 max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full"
        :class="menuOpen && 'max-lg:translate-x-0!'"
    >
        <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col items-start gap-4 pt-12 pb-10">
            <x-clinic-logo size="size-12" />
            <span>
                <span class="block font-serif text-xl leading-tight text-ink">{{ Branding::name() }}</span>
                <span class="eyebrow mt-1 block">{{ Branding::tagline() ?? __('specialties.'.settings('specialty')) }}</span>
            </span>
        </a>

        <nav class="-mx-1 flex-1 space-y-8 overflow-y-auto px-1 pb-8">
            @foreach ($navigation as $group)
                <div>
                    @unless ($loop->first)<p class="eyebrow mb-3 text-neutral-400">{{ $group['label'] }}</p>@endunless
                    <ul>
                        @foreach ($group['items'] as $item)
                            @php $counter++; @endphp
                            <li>
                                <a href="{{ $item['url'] }}" wire:navigate @class(['nav-link', 'active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                                    <span @class(['w-5 font-serif text-[12px] tabular-nums', 'text-primary-600' => $item['active'], 'text-neutral-300' => ! $item['active']])>{{ str_pad($counter, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-line py-6">
            <a href="{{ route('profile') }}" wire:navigate class="block">
                <span class="block text-[13px] text-ink">{{ $user->displayName() }}</span>
                <span class="block text-xs text-muted">{{ $user->primaryRole()?->label() }}</span>
            </a>
            <form method="post" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="eyebrow text-neutral-400 transition hover:text-ink">{{ __('Log out') }}</button>
            </form>
        </div>
    </aside>

    <div class="lg:ps-72">
        <header class="sticky top-0 z-20 flex h-20 items-center gap-6 bg-canvas/90 px-6 backdrop-blur sm:px-10 lg:px-16">
            <button type="button" class="-ms-2 p-2 lg:hidden" x-on:click="menuOpen = true" aria-label="{{ __('Open menu') }}">
                <x-icon name="menu" class="size-6" />
            </button>

            @if (Route::has('patients.index') && $user->can('patients.view'))
                <form action="{{ route('patients.index') }}" method="get" class="relative hidden max-w-sm flex-1 sm:block">
                    <x-icon name="search" class="pointer-events-none absolute start-0 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                    <input type="search" name="search" placeholder="{{ __('Search patients by name, phone or file number…') }}" class="form-control border-line ps-7 text-[13px]">
                </form>
            @endif

            <div class="ms-auto flex items-center gap-6">
                @foreach (config('clinic.locales') as $code => $locale)
                    @continue($code === app()->getLocale())
                    <form method="post" action="{{ route('locale.switch', $code) }}">
                        @csrf
                        <button type="submit" class="eyebrow text-neutral-500 transition hover:text-ink">{{ $locale['name'] }}</button>
                    </form>
                @endforeach
                <span class="hidden h-6 w-px bg-line sm:block"></span>
                <a href="{{ route('profile') }}" wire:navigate class="hidden items-center gap-3 sm:flex">
                    <span class="grid size-9 place-items-center rounded-full border border-line font-serif text-sm text-ink">{{ $user->initials() }}</span>
                </a>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-6 py-8 sm:px-10 lg:px-16 lg:py-12">
            {{ $slot }}
        </main>
    </div>

    <x-toaster />
</body>
</html>
