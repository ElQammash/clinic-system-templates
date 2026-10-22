@php
    use App\Support\Branding;
    use App\Support\Navigation;

    $user = auth()->user();
    $navigation = Navigation::for($user);
    $pageTitle = isset($title) ? __($title) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ Branding::name() }}</title>
    {{-- Apply the saved theme before first paint (dark is the default). --}}
    <script>
        try { if (localStorage.getItem('theme') === 'light') document.documentElement.classList.remove('dark'); } catch (e) {}
    </script>
    @if (Branding::logoUrl())
        <link rel="icon" href="{{ Branding::logoUrl() }}">
    @endif
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ Branding::styleTag() }}
</head>
<body class="min-h-screen"
      x-data="shell()"
      x-on:open-url.window="window.open($event.detail.url ?? $event.detail[0]?.url, '_blank', 'noopener')">
    {{-- Mobile backdrop --}}
    <div x-show="mobile" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/60 lg:hidden" x-on:click="mobile = false"></div>

    {{-- Icon rail sidebar: collapsed (icons only) or pinned open; hovering a collapsed rail peeks the labels. --}}
    <aside
        x-on:mouseenter="hover = true" x-on:mouseleave="hover = false"
        :class="{ 'lg:w-60': pinned || hover, 'max-lg:translate-x-0!': mobile, 'lg:shadow-(--shadow-lift)': hover && ! pinned }"
        class="fixed inset-y-0 start-0 z-40 flex w-60 flex-col border-e border-line bg-surface transition-[width,transform] duration-150 max-lg:ltr:-translate-x-full max-lg:rtl:translate-x-full lg:w-14"
    >
        <div class="flex h-12 items-center gap-2.5 border-b border-line px-3">
            <x-clinic-logo size="size-8" />
            <span class="truncate text-[13px] font-semibold text-ink" :class="(pinned || hover || mobile) ? '' : 'lg:hidden'">{{ Branding::name() }}</span>
        </div>

        <nav class="flex-1 space-y-4 overflow-x-hidden overflow-y-auto px-2 py-3">
            @foreach ($navigation as $group)
                <div>
                    <p class="mb-1 px-2.5 text-[10px] font-semibold tracking-widest text-muted/70 uppercase" :class="(pinned || hover || mobile) ? '' : 'lg:invisible'">{{ $group['label'] }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" wire:navigate title="{{ $item['label'] }}" @class(['nav-link', 'active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                                    <x-icon :name="$item['icon']" :solid="$item['active']" class="size-[18px]" />
                                    <span class="truncate" :class="(pinned || hover || mobile) ? '' : 'lg:hidden'">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="hidden border-t border-line p-2 lg:block">
            <button type="button" x-on:click="togglePin()" class="nav-link w-full" :title="pinned ? @js(__('Collapse sidebar')) : @js(__('Pin sidebar open'))">
                <x-icon name="collapse" class="size-[18px] rtl:-scale-x-100" x-show="pinned" />
                <x-icon name="expand" class="size-[18px] rtl:-scale-x-100" x-show="! pinned" x-cloak />
                <span :class="(pinned || hover) ? '' : 'lg:hidden'" x-text="pinned ? @js(__('Collapse sidebar')) : @js(__('Pin sidebar open'))"></span>
            </button>
        </div>
    </aside>

    <div class="transition-[padding] duration-150" :class="pinned ? 'lg:ps-60' : 'lg:ps-14'">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex h-12 items-center gap-2 border-b border-line bg-surface/85 px-3 backdrop-blur sm:px-4">
            <button type="button" class="btn btn-ghost p-1.5 lg:hidden" x-on:click="mobile = true" aria-label="{{ __('Open menu') }}">
                <x-icon name="menu" class="size-5" />
            </button>

            <nav class="hidden min-w-0 items-center gap-2 text-[13px] sm:flex" aria-label="breadcrumb">
                <a href="{{ route('dashboard') }}" wire:navigate class="truncate text-muted hover:text-ink">{{ Branding::name() }}</a>
                @if ($pageTitle)
                    <span class="text-line-strong">/</span>
                    <span class="truncate font-medium text-ink">{{ $pageTitle }}</span>
                @endif
            </nav>

            <button type="button" x-on:click="$dispatch('open-palette')" class="ms-auto flex h-8 w-full max-w-xs items-center gap-2 rounded-sm border border-line bg-subtle px-2.5 text-[13px] text-muted transition hover:border-line-strong sm:w-64">
                <x-icon name="search" class="size-4" />
                <span class="flex-1 truncate text-start">{{ __('Search…') }}</span>
                <span class="kbd hidden sm:inline-flex" dir="ltr">Ctrl K</span>
            </button>

            <button type="button" x-on:click="toggleTheme()" class="btn btn-ghost p-1.5" :title="dark ? @js(__('Light mode')) : @js(__('Dark mode'))">
                <x-icon name="sun" class="size-[18px]" x-show="dark" />
                <x-icon name="moon" class="size-[18px]" x-show="! dark" x-cloak />
            </button>

            @foreach (config('clinic.locales') as $code => $locale)
                @continue($code === app()->getLocale())
                <form method="post" action="{{ route('locale.switch', $code) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost px-2 py-1.5 font-mono text-xs uppercase" title="{{ $locale['name'] }}">{{ $code }}</button>
                </form>
            @endforeach

            <div x-data="{ open: false }" class="relative">
                <button type="button" x-on:click="open = !open" class="flex items-center gap-2 rounded-sm p-1 hover:bg-subtle" aria-haspopup="true" :aria-expanded="open">
                    <span class="grid size-7 place-items-center rounded-sm bg-primary-500/15 font-mono text-[11px] font-bold text-primary-400">{{ $user->initials() }}</span>
                </button>
                <div x-show="open" x-cloak x-transition.origin.top x-on:click.outside="open = false"
                     class="absolute end-0 mt-1 w-60 rounded-sm border border-line-strong bg-raised py-1 shadow-(--shadow-lift)">
                    <div class="border-b border-line px-3 py-2.5">
                        <p class="truncate text-[13px] font-semibold text-ink">{{ $user->displayName() }}</p>
                        <p class="truncate font-mono text-[11px] text-muted">{{ $user->email }}</p>
                        <p class="mt-1 text-[11px] text-primary-400">{{ $user->primaryRole()?->label() }}</p>
                    </div>
                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 px-3 py-2 text-[13px] text-ink-soft hover:bg-subtle hover:text-ink">
                        <x-icon name="user" class="size-4" /> {{ __('My profile') }}
                    </a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-[13px] text-rose-400 hover:bg-subtle">
                            <x-icon name="logout" class="size-4 rtl:-scale-x-100" /> {{ __('Log out') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1600px] px-3 py-4 sm:px-5 lg:py-5">
            {{ $slot }}
        </main>
    </div>

    <livewire:ui.command-palette />
    <x-toaster />
</body>
</html>
