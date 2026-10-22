{{-- Contact footer + optional custom text from Settings → Printing. Expects $footerText (nullable). --}}
@php use App\Support\Branding; @endphp
<footer class="mt-auto pt-4">
    @if (filled($footerText ?? null))
        <p class="mb-2 text-center text-[11px] text-neutral-700">{{ $footerText }}</p>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t-2 border-neutral-900 pt-1.5 text-[9px] text-neutral-600">
        @if (Branding::address())<span>{{ Branding::address() }}</span>@endif
        <span class="flex gap-3 font-mono" dir="ltr">
            @if (settings('phone'))<span>T {{ settings('phone') }}@if (settings('phone_alt')) / {{ settings('phone_alt') }}@endif</span>@endif
            @if (settings('email'))<span>{{ settings('email') }}</span>@endif
        </span>
    </div>
</footer>
