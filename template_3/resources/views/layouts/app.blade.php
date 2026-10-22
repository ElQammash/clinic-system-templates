@php
    use App\Support\Branding;
    use App\Support\Navigation;

    $user = auth()->user();
    $navigation = Navigation::for($user);
    $pageTitle = isset($title) ? __($title) : null;
    // Warm & Friendly: the first menu group sits directly in the top bar; other groups open as dropdowns.
    $primaryItems = $navigation[0]['items'] ?? [];
    $moreGroups = array_slice($navigation, 1);
    $groupEmoji = ['clinical' => 'stethoscope', 'finance' => 'money', 'admin' => 'settings'];
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
<body class="min-h-screen" x-data="{ menuOpen: false }" x-on:open-url.window="window.open($event.detail.url ?? $event.detail[0]?.url, '_blank', 'noopener')">
    <header class="sticky top-0 z-30 bg-canvas/90 backdrop-blur">
        <div class="mx-auto flex h-18 w-full max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-3">
                <x-clinic-logo size="size-11" />
                <span class="hidden min-w-0 sm:block">
                    <span class="block truncate text-lg leading-tight font-extrabold text-ink">{{ Branding::name() }}</span>
                    <span class="block truncate text-xs font-semibold text-muted">{{ Branding::tagline() ?? __('specialties.'.settings('specialty')) }}</span>
                </span>
            </a>

            @if (Route::has('patients.index') && $user->can('patients.view'))
                <form action="{{ route('patients.index') }}" method="get" class="relative mx-auto hidden w-full max-w-sm md:block">
                    <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-primary-400" />
                    <input type="search" name="search" placeholder="{{ __('Find a patient…') }}" class="form-control rounded-full border-transparent bg-white ps-11 shadow-(--shadow-soft)">
                </form>
            @endif

            <div class="ms-auto flex items-center gap-1.5">
                @foreach (config('clinic.locales') as $code => $locale)
                    @continue($code === app()->getLocale())
                    <form method="post" action="{{ route('locale.switch', $code) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost px-3" title="{{ $locale['name'] }}">
                            <span class="emoji text-lg">🌍</span>
                            <span class="hidden sm:inline">{{ $locale['name'] }}</span>
                        </button>
                    </form>
                @endforeach

                <div x-data="{ open: false }" class="relative">
                    <button type="button" x-on:click="open = !open" class="flex items-center gap-2 rounded-full bg-white p-1 pe-3 shadow-(--shadow-soft) transition hover:shadow-(--shadow-lift)" aria-haspopup="true" :aria-expanded="open">
                        <span class="grid size-9 place-items-center rounded-full bg-secondary-100 text-sm font-extrabold text-secondary-700">{{ $user->initials() }}</span>
                        <span class="hidden max-w-32 truncate text-sm font-bold text-ink lg:block">{{ $user->displayName() }}</span>
                        <x-icon name="chevron-down" class="size-4 text-muted" />
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top x-on:click.outside="open = false"
                         class="absolute end-0 mt-2 w-64 overflow-hidden rounded-3xl border-2 border-line bg-white p-2 shadow-(--shadow-lift)">
                        <div class="mb-1 rounded-2xl bg-cream/60 px-4 py-3">
                            <p class="truncate font-bold">{{ $user->displayName() }}</p>
                            <p class="truncate text-xs text-muted">{{ $user->primaryRole()?->label() }} · <span dir="ltr">{{ $user->email }}</span></p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate class="nav-link"><x-emoji name="id" /> {{ __('My profile') }}</a>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link w-full text-rose-600 hover:bg-rose-50 hover:text-rose-700"><span class="emoji">👋</span> {{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>

                <button type="button" class="grid size-11 place-items-center rounded-full bg-white shadow-(--shadow-soft) lg:hidden" x-on:click="menuOpen = true" aria-label="{{ __('Open menu') }}">
                    <x-icon name="menu" class="size-6 text-ink" />
                </button>
            </div>
        </div>

        {{-- Navigation pills (desktop) --}}
        <nav class="mx-auto hidden w-full max-w-7xl px-4 pb-3 sm:px-6 lg:block lg:px-8" aria-label="{{ __('Main') }}">
            <div class="flex items-center gap-1 rounded-full bg-cream p-1.5">
                @foreach ($primaryItems as $item)
                    <a href="{{ $item['url'] }}" wire:navigate @class(['nav-pill', 'active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                        <x-emoji :name="$item['icon']" class="text-lg" /> {{ $item['label'] }}
                    </a>
                @endforeach

                <span class="mx-1 h-6 w-0.5 rounded-full bg-line"></span>

                @foreach ($moreGroups as $group)
                    @php $groupActive = collect($group['items'])->contains('active', true); @endphp
                    <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                        <button type="button" x-on:click="open = !open" @class(['nav-pill', 'active' => $groupActive]) :aria-expanded="open">
                            <x-emoji :name="$groupEmoji[$group['key']] ?? 'sparkles'" class="text-lg" /> {{ $group['label'] }}
                            <x-icon name="chevron-down" class="size-4 opacity-60 transition" ::class="open && 'rotate-180'" />
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top
                             class="absolute start-0 z-40 mt-2 w-64 rounded-3xl border-2 border-line bg-white p-2 shadow-(--shadow-lift)">
                            @foreach ($group['items'] as $item)
                                <a href="{{ $item['url'] }}" wire:navigate @class(['nav-link', 'active' => $item['active']])>
                                    <x-emoji :name="$item['icon']" class="emoji-tile size-9 text-lg" /> {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </nav>
    </header>

    {{-- Mobile / tablet menu: a sheet of big emoji tiles --}}
    <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div x-show="menuOpen" x-transition.opacity class="absolute inset-0 bg-[#3b2a24]/35 backdrop-blur-sm" x-on:click="menuOpen = false"></div>
        <div x-show="menuOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-[2rem] bg-canvas px-5 pt-3 pb-8">
            <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-line"></div>
            @if (Route::has('patients.index') && $user->can('patients.view'))
                <form action="{{ route('patients.index') }}" method="get" class="relative mb-5">
                    <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-primary-400" />
                    <input type="search" name="search" placeholder="{{ __('Find a patient…') }}" class="form-control rounded-full ps-11">
                </form>
            @endif
            @foreach ($navigation as $group)
                <p class="mb-2 px-1 text-sm font-bold text-muted">{{ $group['label'] }}</p>
                <div class="mb-5 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" wire:navigate x-on:click="menuOpen = false" @class([
                            'flex flex-col items-center gap-1.5 rounded-3xl border-2 p-3 text-center text-sm font-bold transition',
                            'border-primary-300 bg-primary-50 text-primary-700' => $item['active'],
                            'border-line bg-white text-ink hover:border-primary-200' => ! $item['active'],
                        ])>
                            <x-emoji :name="$item['icon']" class="text-3xl" />
                            <span class="leading-tight">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        {{ $slot }}
    </main>

    <x-toaster />
</body>
</html>
