@props(['color' => 'slate'])
@php
    // Dark Pro status pill: square-ish, dot + label, tinted background.
    $tones = [
        'slate' => ['bg-subtle text-muted', 'bg-muted'],
        'primary' => ['bg-primary-500/12 text-primary-400', 'bg-primary-400'],
        'sky' => ['bg-sky-500/12 text-sky-500', 'bg-sky-400'],
        'blue' => ['bg-blue-500/12 text-blue-500', 'bg-blue-400'],
        'violet' => ['bg-violet-500/14 text-violet-500', 'bg-violet-400'],
        'amber' => ['bg-amber-500/14 text-amber-500', 'bg-amber-400'],
        'emerald' => ['bg-emerald-500/12 text-emerald-500', 'bg-emerald-400'],
        'rose' => ['bg-rose-500/12 text-rose-500', 'bg-rose-400'],
        'orange' => ['bg-orange-500/12 text-orange-500', 'bg-orange-400'],
    ];
    [$classes, $dot] = $tones[$color] ?? $tones['slate'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-[3px] px-1.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {$classes}"]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>{{ $slot }}
</span>
