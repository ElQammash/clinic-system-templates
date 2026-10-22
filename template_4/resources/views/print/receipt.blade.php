@php use App\Support\Format; @endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[150mm] flex-col">
        @include('print.partials.letterhead')

        <div class="mt-10 text-center">
            <p class="cap">{{ __('Payment receipt') }}</p>
            <p class="mt-1 text-xs text-muted" dir="ltr">{{ $payment->number }}</p>
            <p class="mt-6 font-serif text-5xl">{{ money($payment->amount) }}</p>
            <p class="mt-2 text-[13px] text-muted">{{ $payment->method->label() }} @if ($payment->reference)· <span dir="ltr">{{ $payment->reference }}</span>@endif</p>
        </div>

        <dl class="mx-auto mt-10 w-full max-w-sm divide-y divide-line border-y border-line text-[13px]">
            <div class="flex justify-between py-2.5"><dt class="text-muted">{{ __('Received from') }}</dt><dd class="font-serif text-base">{{ $payment->patient->name }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-muted">{{ __('Date') }}</dt><dd>{{ Format::dateTime($payment->paid_at) }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-muted">{{ __('For invoice') }}</dt><dd dir="ltr">{{ $invoice->number }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-muted">{{ __('Remaining on invoice') }}</dt><dd>{{ money($invoice->balance) }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-muted">{{ __('Received by') }}</dt><dd>{{ $payment->receiver?->name }}</dd></div>
        </dl>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
