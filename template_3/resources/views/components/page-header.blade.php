@props(['title', 'subtitle' => null, 'emoji' => null])
{{-- Warm & Friendly page header: optional emoji tile + big rounded title. --}}
<div {{ $attributes->merge(['class' => 'mb-7 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="flex items-center gap-4">
        @if ($emoji)
            <x-emoji :name="$emoji" class="emoji-tile size-14 text-3xl shadow-(--shadow-soft)" />
        @endif
        <div>
            <h1 class="text-3xl leading-tight font-extrabold text-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-[15px] text-muted">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
