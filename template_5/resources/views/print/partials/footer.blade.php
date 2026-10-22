{{-- Contact footer + optional custom text from Settings → Printing. Expects $footerText (nullable). --}}
@php use App\Support\Branding; @endphp
<footer class="mt-auto pt-4">
    @if (filled($footerText ?? null))
        <p class="mb-2 text-center text-sm font-bold">{{ $footerText }}</p>
    @endif
    @if (Branding::address() || settings('phone') || settings('email'))
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 rounded-xl bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white">
            @if (Branding::address())<span>{{ Branding::address() }}</span>@endif
            @if (settings('phone'))<span dir="ltr">☎ {{ settings('phone') }}@if (settings('phone_alt')) · {{ settings('phone_alt') }}@endif</span>@endif
            @if (settings('email'))<span dir="ltr">{{ settings('email') }}</span>@endif
        </div>
    @endif
</footer>
