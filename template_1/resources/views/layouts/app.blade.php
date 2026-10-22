@php
    use App\Support\Branding;
    use App\Support\Navigation;

    $user = auth()->user();
    $navigation = Navigation::for($user);
    $pageTitle = isset($title) ? __($title) : null;
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
<body class="min-h-screen" x-data="{ sidebarOpen: false }" x-on:open-url.window="window.open($event.detail.url ?? $event.detail[0]?.url, '_blank', 'noopener')">
    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" x-on:click="sidebarOpen = false"></div>

    {{-- Sidebar: on the start side (right in Arabic, left in English) --}}
    <aside
        class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col border-e border-line bg-white transition-transform duration-200 max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full"
        :class="sidebarOpen && 'max-lg:translate-x-0!'"
    >
        <div class="flex h-16 items-center gap-3 border-b border-line px-5">
            <x-clinic-logo size="size-10" />
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-ink">{{ Branding::name() }}</p>
                <p class="truncate text-xs text-muted">{{ Branding::tagline() ?? __('specialties.'.settings('specialty')) }}</p>
            </div>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
            @foreach ($navigation as $group)
                <div>
                    @if (! $loop->first)
                        <p class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $group['label'] }}</p>
                    @endif
                    <ul class="space-y-1">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" wire:navigate @class(['nav-link', 'active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                                    <x-icon :name="$item['icon']" :solid="$item['active']" class="size-5" />
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-line p-3">
            <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-slate-50">
                <span class="grid size-9 place-items-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">{{ $user->initials() }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $user->displayName() }}</span>
                    <span class="block truncate text-xs text-muted">{{ $user->primaryRole()?->label() }}</span>
                </span>
            </a>
        </div>
    </aside>

    <div class="lg:ps-72">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/85 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn btn-ghost p-2 lg:hidden" x-on:click="sidebarOpen = true" aria-label="{{ __('Open menu') }}">
                <x-icon name="menu" class="size-6" />
            </button>

            @if (Route::has('patients.index') && $user->can('patients.view'))
                <form action="{{ route('patients.index') }}" method="get" class="relative hidden max-w-md flex-1 sm:block">
                    <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="search" placeholder="{{ __('Search patients by name, phone or file number…') }}" class="form-control ps-10">
                </form>
            @else
                <p class="text-sm font-semibold text-ink sm:hidden">{{ $pageTitle }}</p>
            @endif

            <div class="ms-auto flex items-center gap-1">
                @foreach (config('clinic.locales') as $code => $locale)
                    @continue($code === app()->getLocale())
                    <form method="post" action="{{ route('locale.switch', $code) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost gap-1.5 px-3" title="{{ $locale['name'] }}">
                            <x-icon name="language" class="size-5" />
                            <span class="hidden sm:inline">{{ $locale['name'] }}</span>
                        </button>
                    </form>
                @endforeach

                <div x-data="{ open: false }" class="relative">
                    <button type="button" x-on:click="open = !open" class="btn btn-ghost gap-2 px-2" aria-haspopup="true" :aria-expanded="open">
                        <span class="grid size-8 place-items-center rounded-full bg-primary-600 text-xs font-semibold text-white">{{ $user->initials() }}</span>
                        <x-icon name="chevron-down" class="size-4" />
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top x-on:click.outside="open = false"
                         class="absolute end-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-(--shadow-lift)">
                        <div class="border-b border-line px-4 py-3">
                            <p class="truncate text-sm font-semibold">{{ $user->displayName() }}</p>
                            <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50">
                            <x-icon name="user" class="size-5 text-slate-400" /> {{ __('My profile') }}
                        </a>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50">
                                <x-icon name="logout" class="size-5 rtl:-scale-x-100" /> {{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>

    <x-toaster />
</body>
</html>
