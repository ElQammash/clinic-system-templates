@php
    use App\Support\Format;
    $patient = $invoice->patient;
@endphp
<x-print-layout :title="$title" size="a4">
    <div class="flex min-h-[276mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $invoice->doctor])

        <div class="mt-5 grid grid-cols-3 gap-3">
            <div class="box">
                <p class="label">{{ __('Invoice') }}</p>
                <p class="text-xl font-extrabold" dir="ltr" style="text-align: start">{{ $invoice->number }}</p>
                @if ($invoice->isVoid())<p class="mt-1 inline-block rounded-lg bg-rose-600 px-2 py-0.5 text-sm font-extrabold text-white">{{ __('Void') }}</p>@endif
            </div>
            <div class="box">
                <p class="label">{{ __('Patient') }}</p>
                <p class="text-lg leading-tight font-extrabold">{{ $patient->name }}</p>
                <p class="text-xs font-semibold"><span dir="ltr">{{ $patient->file_number }}</span>@if ($patient->phone) · <span dir="ltr">{{ $patient->phone }}</span>@endif</p>
            </div>
            <div class="box">
                <p class="label">{{ __('Date') }}</p>
                <p class="text-lg font-extrabold">{{ Format::date($invoice->issued_at) }}</p>
            </div>
        </div>

        <table class="mt-5 w-full overflow-hidden rounded-xl text-[15px]">
            <thead>
                <tr class="bg-slate-900 text-white">
                    <th class="px-3 py-2.5 text-start font-bold">{{ __('Description') }}</th>
                    <th class="px-3 py-2.5 text-center font-bold">{{ __('Quantity') }}</th>
                    <th class="px-3 py-2.5 text-end font-bold">{{ __('Price') }}</th>
                    <th class="px-3 py-2.5 text-end font-bold">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr class="border-b-2 border-slate-200 even:bg-slate-50">
                        <td class="px-3 py-3 font-semibold">{{ $item->description }}@if ($item->discount > 0)<span class="text-sm text-slate-600"> (− {{ money($item->discount) }})</span>@endif</td>
                        <td class="px-3 py-3 text-center">{{ Format::number($item->quantity) }}</td>
                        <td class="px-3 py-3 text-end">{{ money($item->unit_price) }}</td>
                        <td class="px-3 py-3 text-end font-extrabold">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-5 flex justify-between gap-6">
            <div class="text-sm">
                @if ($invoice->payments->isNotEmpty())
                    <p class="label mb-1">{{ __('Payments') }}</p>
                    @foreach ($invoice->payments as $payment)
                        <p class="font-semibold">{{ Format::date($payment->paid_at) }} — {{ $payment->method->label() }} — {{ money($payment->amount) }} <span dir="ltr">({{ $payment->number }})</span></p>
                    @endforeach
                @endif
                @if ($invoice->notes)<p class="mt-3 max-w-sm">{{ $invoice->notes }}</p>@endif
            </div>
            <dl class="w-80 space-y-1.5 text-[15px]">
                <div class="flex justify-between px-3"><dt>{{ __('Subtotal') }}</dt><dd class="font-bold">{{ money($invoice->subtotal) }}</dd></div>
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between px-3"><dt>{{ __('Discount') }}</dt><dd class="font-bold">− {{ money($invoice->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between rounded-xl bg-slate-100 px-3 py-1.5 text-lg font-extrabold"><dt>{{ __('Total') }}</dt><dd>{{ money($invoice->total) }}</dd></div>
                <div class="flex justify-between px-3"><dt>{{ __('Paid') }}</dt><dd class="font-bold">{{ money($invoice->paid_amount) }}</dd></div>
                <div class="flex justify-between rounded-xl bg-primary-600 px-3 py-2 text-xl font-extrabold text-white"><dt>{{ __('Remaining') }}</dt><dd>{{ money($invoice->balance) }}</dd></div>
            </dl>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
