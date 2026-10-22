@props(['title' => null, 'maxWidth' => 'max-w-2xl'])
{{-- Usage: <x-modal wire:model="showForm" title="..."> ... <x-slot:footer>...</x-slot:footer></x-modal> --}}
{{-- Warm & Friendly: soft cream backdrop, big rounded sheet that pops in with a little bounce. --}}
<div
    x-data="{ open: @entangle($attributes->wire('model')) }"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-[#3b2a24]/35 backdrop-blur-sm" x-on:click="open = false"></div>

    <div class="flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
        <div
            x-show="open"
            x-transition:enter="ease-[cubic-bezier(.34,1.56,.64,1)] duration-300"
            x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-trap.noscroll="open"
            class="relative w-full {{ $maxWidth }} rounded-t-[2rem] border-2 border-line bg-white shadow-(--shadow-lift) sm:rounded-[2rem]"
        >
            <div class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-line sm:hidden"></div>
            @if ($title)
                <div class="flex items-center justify-between px-6 pt-5 pb-2">
                    <h2 class="text-xl font-extrabold text-ink">{{ $title }}</h2>
                    <button type="button" x-on:click="open = false" class="grid size-9 place-items-center rounded-full bg-cream text-stone-600 transition hover:bg-primary-100 hover:text-primary-700" aria-label="{{ __('Close') }}">
                        <x-icon name="close" class="size-5" />
                    </button>
                </div>
            @endif

            <div class="max-h-[75vh] overflow-y-auto px-6 py-4">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-wrap items-center justify-end gap-2 rounded-b-[2rem] bg-cream/50 px-6 py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
