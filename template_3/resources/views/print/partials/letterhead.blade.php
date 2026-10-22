{{-- Warm & Friendly letterhead: rounded cream band, coral initials bubble, doctor on the end side, scalloped dots underneath. Expects optional $doctor (User). --}}
@php use App\Support\Branding; @endphp
<header class="flex items-center justify-between gap-4 rounded-[1.25rem] bg-cream px-4 py-3">
    <div class="flex items-center gap-3">
        @if (settings('print_show_logo') && Branding::logoUrl())
            <img src="{{ Branding::logoUrl() }}" alt="" class="size-12 rounded-full bg-white object-contain p-0.5">
        @else
            <span class="grid size-12 place-items-center rounded-full bg-primary-500 text-base font-extrabold text-white">{{ Branding::initials() }}</span>
        @endif
        <div>
            <p class="text-lg leading-tight font-extrabold text-primary-700">{{ Branding::name() }}</p>
            @if (Branding::tagline())<p class="text-xs text-muted">{{ Branding::tagline() }}</p>@endif
        </div>
    </div>
    @isset($doctor)
        <div class="text-end">
            <p class="text-base font-extrabold">{{ $doctor->displayName() }}</p>
            @if ($doctor->doctorProfile?->title())<p class="text-xs text-muted">{{ $doctor->doctorProfile->title() }}</p>@endif
            @if ($doctor->doctorProfile?->license_number)<p class="text-[10px] text-muted">{{ __('License no.') }} <span dir="ltr">{{ $doctor->doctorProfile->license_number }}</span></p>@endif
        </div>
    @endisset
</header>
<div class="mt-2 flex justify-center gap-1.5" aria-hidden="true">
    @for ($i = 0; $i < 3; $i++)<span class="size-1.5 rounded-full bg-primary-300"></span>@endfor
</div>
