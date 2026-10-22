@props(['title', 'subtitle' => null])
{{-- Luxury Minimal page header: small gold eyebrow (subtitle), large serif title, gold hairline. --}}
<div {{ $attributes->merge(['class' => 'mb-10 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        @if ($subtitle)
            <p class="eyebrow">{{ $subtitle }}</p>
        @endif
        <h1 class="mt-2 font-serif text-4xl leading-tight text-ink sm:text-5xl">{{ $title }}</h1>
        <div class="gold-rule mt-5"></div>
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
