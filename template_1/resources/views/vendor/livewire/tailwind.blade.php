@php
    // Template 1 pagination for Livewire lists (overrides livewire::tailwind).
    $scrollTo = isset($scrollTo) ? $scrollTo : 'body';
    $scrollIntoViewJsSnippet = $scrollTo !== false ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()" : '';
@endphp
<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-4">
            <p class="hidden text-sm text-muted sm:block">
                {!! __('Showing') !!} <span class="font-semibold text-ink">{{ \App\Support\Format::number($paginator->firstItem()) }}</span>
                {!! __('to') !!} <span class="font-semibold text-ink">{{ \App\Support\Format::number($paginator->lastItem()) }}</span>
                {!! __('of') !!} <span class="font-semibold text-ink">{{ \App\Support\Format::number($paginator->total()) }}</span>
            </p>

            <div class="flex flex-1 items-center justify-between gap-1 sm:flex-none sm:justify-end">
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        @disabled($paginator->onFirstPage()) class="btn btn-secondary btn-sm" aria-label="{{ __('pagination.previous') }}">
                    <x-icon name="chevron-right" class="size-4 ltr:rotate-180" />
                </button>

                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="px-2 text-sm text-muted">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <button type="button" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                        @class([
                                            'min-w-8 rounded-lg px-2.5 py-1.5 text-sm font-semibold transition',
                                            'bg-primary-600 text-white shadow-sm' => $page == $paginator->currentPage(),
                                            'text-slate-600 hover:bg-slate-100' => $page != $paginator->currentPage(),
                                        ]) @if ($page == $paginator->currentPage()) aria-current="page" @endif>
                                    {{ \App\Support\Format::number($page) }}
                                </button>
                            @endforeach
                        @endif
                    @endforeach
                </div>

                <span class="text-sm text-muted sm:hidden">{{ \App\Support\Format::number($paginator->currentPage()) }} / {{ \App\Support\Format::number($paginator->lastPage()) }}</span>

                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        @disabled(! $paginator->hasMorePages()) class="btn btn-secondary btn-sm" aria-label="{{ __('pagination.next') }}">
                    <x-icon name="chevron-left" class="size-4 ltr:rotate-180" />
                </button>
            </div>
        </nav>
    @endif
</div>
