@php
    use App\Support\Format;
    $patient = $invoice->patient;
@endphp
<x-print-layout :title="$title" size="a4">
    <div class="flex min-h-[262mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $invoice->doctor])

        <div class="mt-12 flex items-end justify-between gap-8">
            <div>
                <p class="cap">{{ __('Invoice') }}</p>
                <p class="mt-1 font-serif text-4xl" dir="ltr">{{ $invoice->number }}</p>
                @if ($invoice->isVoid())<p class="cap mt-2 text-rose-700">{{ __('Void') }}</p>@endif
            </div>
            <dl class="text-end text-[13px]">
                <dt class="cap">{{ __('Billed to') }}</dt>
                <dd class="mt-1 font-serif text-xl">{{ $patient->name }}</dd>
                <dd class="text-muted"><span dir="ltr">{{ $patient->file_number }}</span>@if ($patient->phone) · <span dir="ltr">{{ $patient->phone }}</span>@endif</dd>
                <dd class="text-muted">{{ Format::date($invoice->issued_at) }}</dd>
            </dl>
        </div>

        <table class="mt-12 w-full text-[13px]">
            <thead>
                <tr class="border-b border-ink">
                    <th class="cap py-3 text-start font-medium">{{ __('Description') }}</th>
                    <th class="cap py-3 text-center font-medium">{{ __('Quantity') }}</th>
                    <th class="cap py-3 text-end font-medium">{{ __('Price') }}</th>
                    <th class="cap py-3 text-end font-medium">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr class="border-b border-line">
                        <td class="py-4">{{ $item->description }}@if ($item->discount > 0)<span class="text-xs text-muted"> (− {{ money($item->discount) }})</span>@endif</td>
                        <td class="py-4 text-center">{{ Format::number($item->quantity) }}</td>
                        <td class="py-4 text-end">{{ money($item->unit_price) }}</td>
                        <td class="py-4 text-end font-serif text-base">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-8 flex justify-end">
            <dl class="w-72 space-y-2 text-[13px]">
                <div class="flex justify-between"><dt class="text-muted">{{ __('Subtotal') }}</dt><dd>{{ money($invoice->subtotal) }}</dd></div>
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Discount') }}</dt><dd>− {{ money($invoice->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-muted">{{ __('Paid') }}</dt><dd>{{ money($invoice->paid_amount) }}</dd></div>
                <div class="flex items-baseline justify-between border-t border-primary-500 pt-3"><dt class="cap">{{ __('Remaining') }}</dt><dd class="font-serif text-2xl">{{ money($invoice->balance) }}</dd></div>
                <div class="flex justify-between text-xs text-muted"><dt>{{ __('Total') }}</dt><dd>{{ money($invoice->total) }}</dd></div>
            </dl>
        </div>

        @if ($invoice->payments->isNotEmpty())
            <div class="mt-10 text-[11px] text-muted">
                <p class="cap mb-2">{{ __('Payments') }}</p>
                @foreach ($invoice->payments as $payment)
                    <p>{{ Format::date($payment->paid_at) }} — {{ $payment->method->label() }} — {{ money($payment->amount) }} <span dir="ltr">({{ $payment->number }})</span></p>
                @endforeach
            </div>
        @endif

        @if ($invoice->notes)<p class="mt-6 font-serif text-sm italic">{{ $invoice->notes }}</p>@endif

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
