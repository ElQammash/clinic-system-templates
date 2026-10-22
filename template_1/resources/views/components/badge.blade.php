@props(['color' => 'slate'])
@php
    $classes = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
    ][$color] ?? 'bg-slate-100 text-slate-700 ring-slate-500/15';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>{{ $slot }}</span>
