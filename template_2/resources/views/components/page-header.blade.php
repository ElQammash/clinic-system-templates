@props(['title', 'subtitle' => null])
{{-- Dark Pro: compact toolbar-style header with a hairline under it. --}}
<div {{ $attributes->merge(['class' => 'mb-4 flex flex-col gap-3 border-b border-line pb-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="truncate text-lg font-semibold tracking-tight text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 truncate text-xs text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
