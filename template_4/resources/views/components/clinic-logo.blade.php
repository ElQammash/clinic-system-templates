@props(['size' => 'size-10'])
@php $logo = \App\Support\Branding::logoUrl(); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="{{ \App\Support\Branding::name() }}" {{ $attributes->merge(['class' => "{$size} object-contain"]) }}>
@else
    {{-- Serif monogram inside a thin gold frame. --}}
    <span {{ $attributes->merge(['class' => "{$size} inline-grid shrink-0 place-items-center border border-primary-500 font-serif text-base leading-none whitespace-nowrap text-primary-700"]) }}>
        {{ \App\Support\Branding::initials() }}
    </span>
@endif
