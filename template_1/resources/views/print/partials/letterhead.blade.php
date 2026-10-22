{{-- Clinic letterhead: logo + clinic on one side, doctor on the other, teal rule underneath. Expects $doctor (User). --}}
@php use App\Support\Branding; @endphp
<header class="flex items-start justify-between gap-6 pb-4">
    <div class="flex items-center gap-3">
        @if (settings('print_show_logo') && Branding::logoUrl())
            <img src="{{ Branding::logoUrl() }}" alt="" class="size-14 object-contain">
        @endif
        <div>
            <p class="text-lg leading-tight font-bold text-primary-700">{{ Branding::name() }}</p>
            @if (Branding::tagline())<p class="text-xs text-slate-500">{{ Branding::tagline() }}</p>@endif
        </div>
    </div>
    @isset($doctor)
        <div class="text-end">
            <p class="text-base font-bold text-slate-900">{{ $doctor->displayName() }}</p>
            @if ($doctor->doctorProfile?->title())<p class="text-xs text-slate-600">{{ $doctor->doctorProfile->title() }}</p>@endif
            @if ($doctor->doctorProfile?->license_number)<p class="text-[10px] text-slate-400">{{ __('License no.') }} <span dir="ltr">{{ $doctor->doctorProfile->license_number }}</span></p>@endif
        </div>
    @endisset
</header>
<div class="h-1 rounded-full bg-gradient-to-l from-primary-600 via-primary-500 to-secondary-500"></div>
