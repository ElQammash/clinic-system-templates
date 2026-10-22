{{-- Luxury Minimal letterhead: centred monogram, serif clinic name, gold hairline, doctor in small caps. Expects optional $doctor (User). --}}
@php use App\Support\Branding; @endphp
<header class="text-center">
    @if (settings('print_show_logo') && Branding::logoUrl())
        <img src="{{ Branding::logoUrl() }}" alt="" class="mx-auto size-12 object-contain">
    @else
        <span class="mx-auto grid size-11 place-items-center border border-primary-500 font-serif text-base text-primary-700">{{ Branding::initials() }}</span>
    @endif
    <p class="mt-3 font-serif text-2xl leading-tight">{{ Branding::name() }}</p>
    @if (Branding::tagline())<p class="cap mt-1">{{ Branding::tagline() }}</p>@endif
    <div class="mx-auto mt-4 h-px w-16 bg-primary-500"></div>
    @isset($doctor)
        <p class="mt-4 font-serif text-lg">{{ $doctor->displayName() }}</p>
        <p class="cap mt-0.5">
            {{ $doctor->doctorProfile?->title() }}
            @if ($doctor->doctorProfile?->license_number) · {{ __('License no.') }} <span dir="ltr">{{ $doctor->doctorProfile->license_number }}</span>@endif
        </p>
    @endisset
</header>
