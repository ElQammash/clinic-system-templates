@props(['color' => 'slate'])
@php
    // Tablet-First: high-contrast solid labels readable from across the desk.
    $classes = [
        'slate' => 'bg-slate-200 text-slate-900',
        'primary' => 'bg-primary-600 text-white',
        'sky' => 'bg-sky-600 text-white',
        'blue' => 'bg-blue-600 text-white',
        'violet' => 'bg-violet-600 text-white',
        'amber' => 'bg-amber-400 text-amber-950',
        'emerald' => 'bg-emerald-600 text-white',
        'rose' => 'bg-rose-600 text-white',
        'orange' => 'bg-orange-500 text-white',
    ][$color] ?? 'bg-slate-200 text-slate-900';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-sm leading-none font-bold whitespace-nowrap {$classes}"]) }}>{{ $slot }}</span>
