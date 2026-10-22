@props(['title' => null, 'maxWidth' => 'max-w-2xl'])
{{-- Usage: <x-modal wire:model="showForm" title="..."> ... <x-slot:footer>...</x-slot:footer></x-modal> --}}
{{-- Luxury Minimal: white sheet on a soft white veil, gold hairline on top, serif title. --}}
<div
    x-data="{ open: @entangle($attributes->wire('model')) }"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity.duration.300ms class="fixed inset-0 bg-white/75 backdrop-blur-sm" x-on:click="open = false"></div>

    <div class="flex min-h-full items-end justify-center sm:items-center sm:p-8">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-trap.noscroll="open"
            class="relative w-full {{ $maxWidth }} border border-line border-t-primary-500 bg-white shadow-(--shadow-lift)"
        >
            @if ($title)
                <div class="flex items-start justify-between gap-4 px-8 pt-8 pb-2">
                    <h2 class="font-serif text-2xl text-ink">{{ $title }}</h2>
                    <button type="button" x-on:click="open = false" class="-me-2 p-2 text-neutral-400 transition hover:text-ink" aria-label="{{ __('Close') }}">
                        <x-icon name="close" class="size-5" />
                    </button>
                </div>
            @endif

            <div class="max-h-[75vh] overflow-y-auto px-8 py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-8 py-5">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
