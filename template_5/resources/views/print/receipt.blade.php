@php use App\Support\Format; @endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[150mm] flex-col">
        @include('print.partials.letterhead')

        <div class="mt-4 flex items-center justify-between">
            <p class="text-2xl font-extrabold">{{ __('Payment receipt') }}</p>
            <p class="rounded-lg bg-slate-900 px-2 py-1 text-sm font-bold text-white" dir="ltr">{{ $payment->number }}</p>
        </div>

        <div class="mt-4 rounded-2xl bg-primary-600 p-5 text-center text-white">
            <p class="text-sm font-bold opacity-90">{{ __('Amount received') }}</p>
            <p class="mt-1 text-5xl font-extrabold">{{ money($payment->amount) }}</p>
            <p class="mt-2 text-lg font-bold">{{ $payment->method->label() }} @if ($payment->reference)· <span dir="ltr">{{ $payment->reference }}</span>@endif</p>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 text-[15px]">
            <div class="box col-span-2"><p class="label">{{ __('Received from') }}</p><p class="text-lg font-extrabold">{{ $payment->patient->name }}</p></div>
            <div class="box"><p class="label">{{ __('Date') }}</p><p class="font-bold">{{ Format::dateTime($payment->paid_at) }}</p></div>
            <div class="box"><p class="label">{{ __('For invoice') }}</p><p class="font-bold" dir="ltr" style="text-align: start">{{ $invoice->number }}</p></div>
            <div class="box"><p class="label">{{ __('Remaining on invoice') }}</p><p class="font-extrabold">{{ money($invoice->balance) }}</p></div>
            <div class="box"><p class="label">{{ __('Received by') }}</p><p class="font-bold">{{ $payment->receiver?->name ?? '—' }}</p></div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
