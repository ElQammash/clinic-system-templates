{{-- Tablet-First letterhead: solid indigo→purple band with white type, doctor on the end side. Expects optional $doctor (User). --}}
@php use App\Support\Branding; @endphp
<header class="flex items-center justify-between gap-4 rounded-2xl bg-gradient-to-l from-primary-600 to-secondary-600 px-4 py-3 text-white">
    <div class="flex items-center gap-3">
        @if (settings('print_show_logo') && Branding::logoUrl())
            <img src="{{ Branding::logoUrl() }}" alt="" class="size-12 rounded-xl bg-white object-contain p-0.5">
        @else
            <span class="grid size-12 place-items-center rounded-xl bg-white/20 text-lg font-extrabold">{{ Branding::initials() }}</span>
        @endif
        <div>
            <p class="text-xl leading-tight font-extrabold">{{ Branding::name() }}</p>
            @if (Branding::tagline())<p class="text-xs text-white/80">{{ Branding::tagline() }}</p>@endif
        </div>
    </div>
    @isset($doctor)
        <div class="text-end">
            <p class="text-lg font-extrabold">{{ $doctor->displayName() }}</p>
            @if ($doctor->doctorProfile?->title())<p class="text-xs text-white/85">{{ $doctor->doctorProfile->title() }}</p>@endif
            @if ($doctor->doctorProfile?->license_number)<p class="text-[10px] text-white/70">{{ __('License no.') }} <span dir="ltr">{{ $doctor->doctorProfile->license_number }}</span></p>@endif
        </div>
    @endisset
</header>
