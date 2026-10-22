@props(['size' => 'size-10'])
@php $logo = \App\Support\Branding::logoUrl(); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="{{ \App\Support\Branding::name() }}" {{ $attributes->merge(['class' => "{$size} rounded-2xl bg-white object-contain p-0.5"]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$size} inline-grid shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-primary-600 to-secondary-600 text-sm leading-none font-extrabold whitespace-nowrap text-white"]) }}>
        {{ \App\Support\Branding::initials() }}
    </span>
@endif
