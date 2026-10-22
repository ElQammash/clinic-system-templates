@props(['size' => 'size-8'])
@php $logo = \App\Support\Branding::logoUrl(); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="{{ \App\Support\Branding::name() }}" {{ $attributes->merge(['class' => "{$size} rounded-sm bg-white object-contain p-0.5"]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$size} inline-grid shrink-0 place-items-center rounded-sm border leading-none whitespace-nowrap border-primary-500/40 bg-primary-500/10 font-mono text-xs font-bold text-primary-400"]) }}>
        {{ \App\Support\Branding::initials() }}
    </span>
@endif
