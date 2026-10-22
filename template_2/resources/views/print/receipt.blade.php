@php use App\Support\Format; @endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[150mm] flex-col">
        @include('print.partials.letterhead')

        <div class="mt-4 flex items-end justify-between">
            <div>
                <p class="cap">{{ __('Payment receipt') }}</p>
                <p class="font-mono text-lg font-bold" dir="ltr">{{ $payment->number }}</p>
            </div>
            <p class="font-mono text-[11px] text-neutral-600" dir="ltr">{{ $payment->paid_at->format('Y-m-d H:i') }}</p>
        </div>

        <div class="mt-3 flex border border-neutral-900">
            <div class="flex-1 px-4 py-3">
                <p class="cap">{{ __('Amount received') }}</p>
                <p class="mt-1 font-mono text-3xl font-bold">{{ money($payment->amount) }}</p>
            </div>
            <div class="flex flex-col justify-center border-s border-neutral-900 bg-neutral-950 px-4 text-white">
                <p class="text-[8px] tracking-[0.2em] text-neutral-400 uppercase">{{ __('Method') }}</p>
                <p class="text-sm font-semibold">{{ $payment->method->label() }}</p>
                @if ($payment->reference)<p class="font-mono text-[10px] text-neutral-400" dir="ltr">{{ $payment->reference }}</p>@endif
            </div>
        </div>

        <div class="mt-3 grid grid-cols-2 border border-neutral-900 text-[11px]">
            <div class="cell border-e border-b"><p class="cap">{{ __('Received from') }}</p><p class="font-semibold">{{ $payment->patient->name }}</p></div>
            <div class="cell border-b"><p class="cap">{{ __('For invoice') }}</p><p class="font-mono font-semibold" dir="ltr">{{ $invoice->number }}</p></div>
            <div class="cell border-e"><p class="cap">{{ __('Remaining on invoice') }}</p><p class="font-mono font-semibold">{{ money($invoice->balance) }}</p></div>
            <div class="cell"><p class="cap">{{ __('Received by') }}</p><p>{{ $payment->receiver?->name ?? '—' }}</p></div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
