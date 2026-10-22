@php
    use App\Support\Branding;
    use App\Support\Navigation;

    $user = auth()->user();
    $navigation = Navigation::for($user);
    $flat = collect(Navigation::flat($user));
    $pageTitle = isset($title) ? __($title) : null;

    // Tablet-First: the four busiest reception destinations live in the bottom tab bar; everything else is under "More".
    $tabRoutes = ['dashboard', 'queue', 'appointments.index', 'patients.index', 'invoices.index'];
    $tabs = $flat->filter(fn ($item) => in_array($item['route'], $tabRoutes, true))
        ->sortBy(fn ($item) => array_search($item['route'], $tabRoutes, true))->take(4)->values();
    $moreActive = $flat->contains(fn ($item) => $item['active'] && ! $tabs->contains('route', $item['route']));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ Branding::name() }}</title>
    @if (Branding::logoUrl())
        <link rel="icon" href="{{ Branding::logoUrl() }}">
    @endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ Branding::styleTag() }}
</head>
<body class="min-h-screen pb-32" x-data="{ more: false }" x-on:open-url.window="window.open($event.detail.url ?? $event.detail[0]?.url, '_blank', 'noopener')">
    <header class="sticky top-0 z-30 border-b-2 border-line bg-white">
        <div class="mx-auto flex h-18 w-full max-w-[1400px] items-center gap-4 px-4 sm:px-6">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-3">
                <x-clinic-logo size="size-12" />
                <span class="hidden min-w-0 md:block">
                    <span class="block truncate text-lg leading-tight font-extrabold">{{ Branding::name() }}</span>
                    <span class="block truncate text-sm font-semibold text-muted">{{ Branding::tagline() ?? __('specialties.'.settings('specialty')) }}</span>
                </span>
            </a>

            {{-- Big clock: readable from across the desk --}}
            <div class="mx-auto hidden text-center lg:block" x-data="{ t: '', d: '' }" x-init="const f = () => { const n = new Date(); t = n.toLocaleTimeString('{{ app()->getLocale() }}-EG', { hour: '2-digit', minute: '2-digit' }); d = n.toLocaleDateString('{{ app()->getLocale() }}-EG', { weekday: 'long', day: 'numeric', month: 'long' }); }; f(); setInterval(f, 15000)">
                <p class="text-2xl leading-none font-extrabold text-primary-700" x-text="t"></p>
                <p class="mt-1 text-sm font-semibold text-muted" x-text="d"></p>
            </div>

            <div class="ms-auto flex items-center gap-2">
                @if (Route::has('patients.index') && $user->can('patients.view'))
                    <form action="{{ route('patients.index') }}" method="get" class="relative hidden sm:block">
                        <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-6 -translate-y-1/2 text-slate-500" />
                        <input type="search" name="search" placeholder="{{ __('Name, phone or file no.') }}" class="form-control w-64 ps-12 xl:w-80">
                    </form>
                @endif
                @foreach (config('clinic.locales') as $code => $locale)
                    @continue($code === app()->getLocale())
                    <form method="post" action="{{ route('locale.switch', $code) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary px-4" title="{{ $locale['name'] }}">{{ strtoupper($code) === 'AR' ? 'ع' : strtoupper($code) }}</button>
                    </form>
                @endforeach
                <div x-data="{ open: false }" class="relative">
                    <button type="button" x-on:click="open = !open" class="grid size-12 place-items-center rounded-2xl bg-primary-100 text-base font-extrabold text-primary-800" aria-haspopup="true" :aria-expanded="open">{{ $user->initials() }}</button>
                    <div x-show="open" x-cloak x-transition.origin.top x-on:click.outside="open = false" class="absolute end-0 mt-2 w-72 rounded-3xl border-2 border-line bg-white p-2 shadow-(--shadow-lift)">
                        <div class="px-4 py-3">
                            <p class="truncate text-lg font-extrabold">{{ $user->displayName() }}</p>
                            <p class="truncate text-sm text-muted">{{ $user->primaryRole()?->label() }}</p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate class="nav-link"><x-icon name="user" class="size-6" /> {{ __('My profile') }}</a>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link w-full text-rose-700 hover:bg-rose-50"><x-icon name="logout" class="size-6 rtl:-scale-x-100" /> {{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6">
        {{ $slot }}
    </main>

    {{-- Bottom tab bar --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t-2 border-line bg-white/95 px-3 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] backdrop-blur" aria-label="{{ __('Main') }}">
        <div class="mx-auto flex max-w-3xl gap-2">
            @foreach ($tabs as $item)
                <a href="{{ $item['url'] }}" wire:navigate @class(['tab-item', 'active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" :solid="$item['active']" class="size-7" />
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
            <button type="button" x-on:click="more = true" @class(['tab-item', 'active' => $moreActive])>
                <x-icon name="menu" class="size-7" />
                <span>{{ __('More') }}</span>
            </button>
        </div>
    </nav>

    {{-- "More" sheet --}}
    <div x-show="more" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <div x-show="more" x-transition.opacity class="absolute inset-0 bg-primary-950/50" x-on:click="more = false"></div>
        <div x-show="more" x-transition:enter="ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-[2rem] bg-canvas px-5 pt-4 pb-10">
            <div class="mx-auto max-w-4xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-2xl font-extrabold">{{ __('All sections') }}</h2>
                    <button type="button" x-on:click="more = false" class="grid size-12 place-items-center rounded-2xl bg-white" aria-label="{{ __('Close') }}"><x-icon name="close" class="size-6" /></button>
                </div>
                @foreach ($navigation as $group)
                    <p class="mt-4 mb-2 text-base font-bold text-muted">{{ $group['label'] }}</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($group['items'] as $item)
                            <a href="{{ $item['url'] }}" wire:navigate x-on:click="more = false" @class([
                                'flex min-h-24 items-center gap-3 rounded-3xl border-2 p-4 text-lg font-bold transition',
                                'border-primary-600 bg-primary-600 text-white' => $item['active'],
                                'border-line bg-white text-ink hover:border-primary-400' => ! $item['active'],
                            ])>
                                <x-icon :name="$item['icon']" class="size-8 shrink-0" />
                                <span class="leading-tight">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-toaster />
</body>
</html>
