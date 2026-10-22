@props(['color' => 'slate'])
@php
    // Luxury Minimal: no fills — a small colored dot + quiet text.
    $dot = [
        'slate' => 'bg-neutral-400',
        'primary' => 'bg-primary-500',
        'sky' => 'bg-sky-600',
        'blue' => 'bg-blue-700',
        'violet' => 'bg-violet-600',
        'amber' => 'bg-amber-500',
        'emerald' => 'bg-emerald-600',
        'rose' => 'bg-rose-600',
        'orange' => 'bg-orange-500',
    ][$color] ?? 'bg-neutral-400';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-[12px] whitespace-nowrap text-neutral-600']) }}><span class="size-1.5 rounded-full {{ $dot }}"></span>{{ $slot }}</span>
