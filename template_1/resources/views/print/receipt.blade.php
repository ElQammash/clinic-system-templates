@php use App\Support\Format; @endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[150mm] flex-col">
        @include('print.partials.letterhead')

        <div class="mt-5 text-center">
            <p class="text-xl font-bold text-slate-900">{{ __('Payment receipt') }}</p>
            <p class="mt-1 font-mono text-xs text-slate-500" dir="ltr">{{ $payment->number }}</p>
        </div>

        <div class="mx-auto mt-5 w-full max-w-sm rounded-xl border-2 border-dashed border-primary-200 p-5 text-center">
            <p class="text-sm text-slate-500">{{ __('Amount received') }}</p>
            <p class="mt-1 text-3xl font-bold text-primary-700">{{ money($payment->amount) }}</p>
            <p class="mt-1 text-sm">{{ $payment->method->label() }} @if ($payment->reference)· <span dir="ltr">{{ $payment->reference }}</span>@endif</p>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <dt class="text-slate-500">{{ __('Received from') }}</dt><dd class="font-semibold">{{ $payment->patient->name }}</dd>
            <dt class="text-slate-500">{{ __('Date') }}</dt><dd>{{ Format::dateTime($payment->paid_at) }}</dd>
            <dt class="text-slate-500">{{ __('For invoice') }}</dt><dd dir="ltr" class="text-start">{{ $invoice->number }}</dd>
            <dt class="text-slate-500">{{ __('Remaining on invoice') }}</dt><dd class="font-semibold">{{ money($invoice->balance) }}</dd>
            <dt class="text-slate-500">{{ __('Received by') }}</dt><dd>{{ $payment->receiver?->name }}</dd>
        </dl>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
