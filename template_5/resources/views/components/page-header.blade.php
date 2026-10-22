@props(['title', 'subtitle' => null])
{{-- Tablet-First page header: bold title, actions rendered as large touch buttons. --}}
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-3xl font-extrabold text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-base font-semibold text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3 [&>.btn]:min-h-14 [&>.btn]:px-6 [&>.btn]:text-lg">{{ $actions }}</div>
    @endisset
</div>
