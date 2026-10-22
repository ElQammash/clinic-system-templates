@props(['size' => 'size-10'])
@php $logo = \App\Support\Branding::logoUrl(); @endphp
@if ($logo)
    <img src="{{ $logo }}" alt="{{ \App\Support\Branding::name() }}" {{ $attributes->merge(['class' => "{$size} rounded-full bg-white object-contain p-0.5"]) }}>
@else
    {{-- Initials in a coral bubble with a little heart badge. --}}
    <span {{ $attributes->merge(['class' => "{$size} relative inline-grid shrink-0 place-items-center rounded-full bg-primary-500 text-sm leading-none font-extrabold whitespace-nowrap text-white shadow-[0_3px_0_var(--color-primary-700)]"]) }}>
        {{ \App\Support\Branding::initials() }}
        <span class="emoji absolute -end-1 -top-1 text-[0.8em]" aria-hidden="true">❤️</span>
    </span>
@endif
