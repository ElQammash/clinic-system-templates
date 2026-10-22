@props(['title' => null, 'maxWidth' => 'max-w-2xl'])
{{-- Usage: <x-modal wire:model="showForm" title="..."> ... <x-slot:footer>...</x-slot:footer></x-modal> --}}
{{-- Tablet-First: a full-height side sheet on tablets (easy to reach with a thumb), bottom sheet on phones. --}}
<div
    x-data="{ open: @entangle($attributes->wire('model')) }"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-primary-950/50" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="max-sm:translate-y-full sm:ltr:translate-x-full sm:rtl:-translate-x-full"
        x-transition:enter-end="translate-x-0 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="translate-x-0 translate-y-0"
        x-transition:leave-end="max-sm:translate-y-full sm:ltr:translate-x-full sm:rtl:-translate-x-full"
        x-trap.noscroll="open"
        class="fixed flex flex-col bg-white shadow-(--shadow-lift) max-sm:inset-x-0 max-sm:bottom-0 max-sm:max-h-[92vh] max-sm:rounded-t-3xl sm:inset-y-0 sm:end-0 sm:w-full sm:{{ $maxWidth }}"
    >
        @if ($title)
            <div class="flex items-center justify-between gap-3 border-b-2 border-line px-6 py-4">
                <h2 class="text-2xl font-extrabold text-ink">{{ $title }}</h2>
                <button type="button" x-on:click="open = false" class="grid size-12 place-items-center rounded-2xl bg-slate-100 text-ink hover:bg-slate-200" aria-label="{{ __('Close') }}">
                    <x-icon name="close" class="size-6" />
                </button>
            </div>
        @endif

        <div class="flex-1 overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex flex-wrap items-center justify-end gap-3 border-t-2 border-line bg-slate-50 px-6 py-4 [&_.btn]:min-h-14 [&_.btn]:px-7">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
