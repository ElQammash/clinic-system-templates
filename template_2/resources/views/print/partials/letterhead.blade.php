{{-- Dark Pro letterhead: solid black band, clinic on the start side, doctor on the end side, accent tick. Expects optional $doctor (User). --}}
@php use App\Support\Branding; @endphp
<header class="flex items-stretch bg-neutral-950 text-white">
    <div class="w-1.5 shrink-0 bg-primary-500"></div>
    <div class="flex flex-1 items-center gap-3 px-4 py-3">
        @if (settings('print_show_logo') && Branding::logoUrl())
            <img src="{{ Branding::logoUrl() }}" alt="" class="size-11 bg-white object-contain p-0.5">
        @endif
        <div class="min-w-0">
            <p class="text-base leading-tight font-bold">{{ Branding::name() }}</p>
            @if (Branding::tagline())<p class="text-[10px] text-neutral-400">{{ Branding::tagline() }}</p>@endif
        </div>
    </div>
    @isset($doctor)
        <div class="flex flex-col justify-center border-s border-neutral-700 px-4 py-3 text-end">
            <p class="text-sm font-bold">{{ $doctor->displayName() }}</p>
            @if ($doctor->doctorProfile?->title())<p class="text-[10px] text-neutral-300">{{ $doctor->doctorProfile->title() }}</p>@endif
            @if ($doctor->doctorProfile?->license_number)<p class="font-mono text-[9px] text-neutral-500"><span>{{ __('License no.') }}</span> <span dir="ltr">{{ $doctor->doctorProfile->license_number }}</span></p>@endif
        </div>
    @endisset
</header>
