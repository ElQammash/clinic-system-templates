@props(['title' => null, 'maxWidth' => 'max-w-2xl'])
{{-- Usage: <x-modal wire:model="showForm" title="..."> ... <x-slot:footer>...</x-slot:footer></x-modal> --}}
<div
    x-data="{ open: @entangle($attributes->wire('model')) }"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]" x-on:click="open = false"></div>

    <div class="flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-trap.noscroll="open"
            class="relative w-full {{ $maxWidth }} rounded-t-2xl bg-white shadow-(--shadow-lift) sm:rounded-2xl"
        >
            @if ($title)
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                    <button type="button" x-on:click="open = false" class="btn btn-ghost -me-2 p-1.5" aria-label="{{ __('Close') }}">
                        <x-icon name="close" class="size-5" />
                    </button>
                </div>
            @endif

            <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-line bg-slate-50/70 px-6 py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
