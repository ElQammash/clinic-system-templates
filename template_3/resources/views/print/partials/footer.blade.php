{{-- Contact footer + optional custom text from Settings → Printing. Expects $footerText (nullable). --}}
@php use App\Support\Branding; @endphp
<footer class="mt-auto pt-4">
    @if (filled($footerText ?? null))
        <p class="mb-2 text-center text-sm font-bold text-primary-700">{{ $footerText }}</p>
    @endif
    @if (Branding::address() || settings('phone') || settings('email'))
    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 rounded-full bg-cream px-4 py-1.5 text-[10px] font-semibold text-muted">
        @if (Branding::address())<span>📍 {{ Branding::address() }}</span>@endif
        @if (settings('phone'))<span dir="ltr">📞 {{ settings('phone') }}@if (settings('phone_alt')) · {{ settings('phone_alt') }}@endif</span>@endif
        @if (settings('email'))<span dir="ltr">✉️ {{ settings('email') }}</span>@endif
    </div>
    @endif
</footer>
