@php use App\Support\Format; @endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[150mm] flex-col">
        @include('print.partials.letterhead')

        <div class="mt-5 text-center">
            <p class="text-2xl font-extrabold">{{ __('Payment receipt') }} 🎉</p>
            <p class="mt-1 text-xs font-bold text-muted" dir="ltr">{{ $payment->number }}</p>
        </div>

        <div class="mx-auto mt-5 w-full max-w-sm rounded-[1.75rem] bg-primary-50 p-5 text-center">
            <p class="text-sm font-bold text-primary-700">{{ __('Amount received') }}</p>
            <p class="mt-1 text-4xl font-extrabold text-primary-600">{{ money($payment->amount) }}</p>
            <p class="mt-2 inline-block rounded-full bg-white px-3 py-0.5 text-sm font-bold">{{ $payment->method->label() }} @if ($payment->reference)· <span dir="ltr">{{ $payment->reference }}</span>@endif</p>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-2 text-sm">
            @foreach ([
                [__('Received from'), $payment->patient->name, false],
                [__('Date'), Format::dateTime($payment->paid_at), false],
                [__('For invoice'), $invoice->number, true],
                [__('Remaining on invoice'), money($invoice->balance), false],
                [__('Received by'), $payment->receiver?->name ?? '—', false],
            ] as [$label, $value, $ltr])
                <div class="rounded-2xl border-2 border-line px-3 py-2"><dt class="text-xs text-muted">{{ $label }}</dt><dd class="font-extrabold" @if ($ltr) dir="ltr" style="text-align: start" @endif>{{ $value }}</dd></div>
            @endforeach
        </dl>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
