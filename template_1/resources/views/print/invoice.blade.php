@php
    use App\Support\Format;
    $patient = $invoice->patient;
@endphp
<x-print-layout :title="$title" size="a4">
    <div class="flex min-h-[270mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $invoice->doctor])

        <div class="mt-6 flex items-start justify-between gap-6">
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ __('Invoice') }}</p>
                <p class="mt-1 font-mono text-sm text-slate-600" dir="ltr">{{ $invoice->number }}</p>
                @if ($invoice->isVoid())<p class="mt-2 inline-block rounded border-2 border-rose-500 px-3 py-1 text-sm font-bold text-rose-600">{{ __('Void') }}</p>@endif
            </div>
            <dl class="grid grid-cols-[auto_auto] gap-x-4 gap-y-1 text-sm">
                <dt class="text-slate-500">{{ __('Date') }}</dt><dd class="font-semibold">{{ Format::date($invoice->issued_at) }}</dd>
                <dt class="text-slate-500">{{ __('Patient') }}</dt><dd class="font-semibold">{{ $patient->name }}</dd>
                <dt class="text-slate-500">{{ __('File no.') }}</dt><dd class="font-semibold" dir="ltr">{{ $patient->file_number }}</dd>
                @if ($patient->phone)<dt class="text-slate-500">{{ __('Phone') }}</dt><dd dir="ltr">{{ $patient->phone }}</dd>@endif
            </dl>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead>
                <tr class="bg-primary-600 text-white">
                    <th class="rounded-s-md px-3 py-2 text-start font-semibold">{{ __('Description') }}</th>
                    <th class="px-3 py-2 text-center font-semibold">{{ __('Quantity') }}</th>
                    <th class="px-3 py-2 text-end font-semibold">{{ __('Price') }}</th>
                    <th class="rounded-e-md px-3 py-2 text-end font-semibold">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr class="border-b border-slate-200">
                        <td class="px-3 py-2.5">{{ $item->description }}@if ($item->discount > 0)<span class="text-xs text-slate-500"> (− {{ money($item->discount) }})</span>@endif</td>
                        <td class="px-3 py-2.5 text-center">{{ Format::number($item->quantity) }}</td>
                        <td class="px-3 py-2.5 text-end">{{ money($item->unit_price) }}</td>
                        <td class="px-3 py-2.5 text-end font-semibold">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 flex justify-end">
            <dl class="w-72 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd>{{ money($invoice->subtotal) }}</dd></div>
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('Discount') }}</dt><dd>− {{ money($invoice->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between border-t-2 border-primary-600 pt-2 text-base font-bold"><dt>{{ __('Total') }}</dt><dd>{{ money($invoice->total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">{{ __('Paid') }}</dt><dd>{{ money($invoice->paid_amount) }}</dd></div>
                <div class="flex justify-between rounded bg-primary-50 px-2 py-1 font-bold"><dt>{{ __('Remaining') }}</dt><dd>{{ money($invoice->balance) }}</dd></div>
            </dl>
        </div>

        @if ($invoice->payments->isNotEmpty())
            <div class="mt-6 text-xs text-slate-600">
                <p class="mb-1 font-semibold text-slate-700">{{ __('Payments') }}</p>
                @foreach ($invoice->payments as $payment)
                    <p>{{ Format::date($payment->paid_at) }} — {{ $payment->method->label() }} — {{ money($payment->amount) }} <span dir="ltr">({{ $payment->number }})</span></p>
                @endforeach
            </div>
        @endif

        @if ($invoice->notes)<p class="mt-4 text-xs text-slate-600">{{ $invoice->notes }}</p>@endif

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
