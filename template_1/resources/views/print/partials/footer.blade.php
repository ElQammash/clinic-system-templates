{{-- Contact footer + optional custom text from Settings → Printing. Expects $footerText (nullable). --}}
@php use App\Support\Branding; @endphp
<footer class="mt-auto pt-4">
    @if (filled($footerText ?? null))
        <p class="mb-2 text-center text-xs text-slate-600">{{ $footerText }}</p>
    @endif
    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 border-t border-primary-200 pt-2 text-[10px] text-slate-500">
        @if (Branding::address())<span>{{ Branding::address() }}</span>@endif
        @if (settings('phone'))<span dir="ltr">☎ {{ settings('phone') }}@if (settings('phone_alt')) · {{ settings('phone_alt') }}@endif</span>@endif
        @if (settings('email'))<span dir="ltr">{{ settings('email') }}</span>@endif
    </div>
</footer>
