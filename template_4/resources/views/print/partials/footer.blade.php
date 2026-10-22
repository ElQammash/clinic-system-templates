{{-- Contact footer + optional custom text from Settings → Printing. Expects $footerText (nullable). --}}
@php use App\Support\Branding; @endphp
<footer class="mt-auto pt-6 text-center">
    @if (filled($footerText ?? null))
        <p class="mb-3 font-serif text-sm text-neutral-600 italic">{{ $footerText }}</p>
    @endif
    <div class="mx-auto mb-3 h-px w-8 bg-primary-500"></div>
    <p class="cap flex flex-wrap justify-center gap-x-4 gap-y-1">
        @if (Branding::address())<span>{{ Branding::address() }}</span>@endif
        @if (settings('phone'))<span dir="ltr">{{ settings('phone') }}@if (settings('phone_alt')) · {{ settings('phone_alt') }}@endif</span>@endif
        @if (settings('email'))<span dir="ltr">{{ settings('email') }}</span>@endif
    </p>
</footer>
