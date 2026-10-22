@props(['title' => null, 'maxWidth' => 'max-w-2xl'])
{{-- Dark Pro dialog: sharp panel sliding from the top, hairline borders. --}}
<div
    x-data="{ open: @entangle($attributes->wire('model')) }"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/60" x-on:click="open = false"></div>

    <div class="flex min-h-full items-end justify-center sm:items-start sm:px-6 sm:pt-[8vh] sm:pb-6">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-trap.noscroll="open"
            class="relative w-full {{ $maxWidth }} border border-line-strong bg-surface shadow-(--shadow-lift) sm:rounded-(--radius-card)"
        >
            @if ($title)
                <div class="flex items-center justify-between border-b border-line px-5 py-3">
                    <h2 class="text-sm font-semibold tracking-wide text-ink">{{ $title }}</h2>
                    <div class="flex items-center gap-2">
                        <span class="kbd hidden sm:inline-flex">Esc</span>
                        <button type="button" x-on:click="open = false" class="btn btn-ghost -me-1 p-1" aria-label="{{ __('Close') }}">
                            <x-icon name="close" class="size-4" />
                        </button>
                    </div>
                </div>
            @endif

            <div class="max-h-[72vh] overflow-y-auto px-5 py-4">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line bg-subtle px-5 py-3">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
