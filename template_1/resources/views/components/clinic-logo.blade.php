@props(['size' => 'size-10'])
@php $logo = \App\Support\Branding::logoUrl(); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="{{ \App\Support\Branding::name() }}" {{ $attributes->merge(['class' => "{$size} rounded-xl object-contain"]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$size} inline-grid place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-secondary-600 text-sm font-bold text-white shadow-sm"]) }}>
        {{ \App\Support\Branding::initials() }}
    </span>
@endif
