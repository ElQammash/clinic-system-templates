@props(['color' => 'slate'])
@php
    // Warm & Friendly: chunky candy pills, no rings.
    $classes = [
        'slate' => 'bg-stone-100 text-stone-700',
        'primary' => 'bg-primary-100 text-primary-700',
        'sky' => 'bg-sky-100 text-sky-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'violet' => 'bg-violet-100 text-violet-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'rose' => 'bg-rose-100 text-rose-700',
        'orange' => 'bg-orange-100 text-orange-800',
    ][$color] ?? 'bg-stone-100 text-stone-700';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-3 py-0.5 text-[13px] font-bold whitespace-nowrap {$classes}"]) }}>{{ $slot }}</span>
