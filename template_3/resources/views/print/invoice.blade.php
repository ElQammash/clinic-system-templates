@php
    use App\Support\Format;
    $patient = $invoice->patient;
@endphp
<x-print-layout :title="$title" size="a4">
    <div class="flex min-h-[270mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $invoice->doctor])

        <div class="mt-6 flex items-start justify-between gap-6">
            <div>
                <p class="text-3xl font-extrabold">{{ __('Invoice') }} 🧾</p>
                <p class="mt-1 text-sm font-bold text-muted" dir="ltr">{{ $invoice->number }}</p>
                @if ($invoice->isVoid())<p class="mt-2 inline-block rounded-full bg-rose-100 px-4 py-1 text-sm font-extrabold text-rose-700">{{ __('Void') }}</p>@endif
            </div>
            <div class="rounded-[1.25rem] border-2 border-line px-4 py-3 text-sm">
                <p class="font-extrabold">{{ $patient->name }}</p>
                <p class="text-muted">{{ __('File no.') }} <span dir="ltr">{{ $patient->file_number }}</span>@if ($patient->phone) · <span dir="ltr">{{ $patient->phone }}</span>@endif</p>
                <p class="text-muted">{{ __('Date') }}: {{ Format::date($invoice->issued_at) }}</p>
            </div>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead>
                <tr class="bg-primary-50 text-primary-900">
                    <th class="rounded-s-full px-4 py-2.5 text-start font-extrabold">{{ __('Description') }}</th>
                    <th class="px-3 py-2.5 text-center font-extrabold">{{ __('Quantity') }}</th>
                    <th class="px-3 py-2.5 text-end font-extrabold">{{ __('Price') }}</th>
                    <th class="rounded-e-full px-4 py-2.5 text-end font-extrabold">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr class="border-b-2 border-dashed border-line">
                        <td class="px-4 py-3 font-semibold">{{ $item->description }}@if ($item->discount > 0)<span class="text-xs text-muted"> (− {{ money($item->discount) }})</span>@endif</td>
                        <td class="px-3 py-3 text-center">{{ Format::number($item->quantity) }}</td>
                        <td class="px-3 py-3 text-end">{{ money($item->unit_price) }}</td>
                        <td class="px-4 py-3 text-end font-extrabold">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-5 flex justify-between gap-6">
            <div class="text-xs text-muted">
                @if ($invoice->payments->isNotEmpty())
                    <p class="mb-1 font-extrabold text-ink">{{ __('Payments') }}</p>
                    @foreach ($invoice->payments as $payment)
                        <p>{{ Format::date($payment->paid_at) }} — {{ $payment->method->label() }} — {{ money($payment->amount) }} <span dir="ltr">({{ $payment->number }})</span></p>
                    @endforeach
                @endif
                @if ($invoice->notes)<p class="mt-3 max-w-sm">{{ $invoice->notes }}</p>@endif
            </div>
            <dl class="w-72 space-y-1.5 rounded-[1.25rem] bg-cream/60 p-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('Subtotal') }}</dt><dd>{{ money($invoice->subtotal) }}</dd></div>
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Discount') }}</dt><dd>− {{ money($invoice->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between text-base font-extrabold"><dt>{{ __('Total') }}</dt><dd>{{ money($invoice->total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('Paid') }}</dt><dd>{{ money($invoice->paid_amount) }}</dd></div>
                <div class="flex justify-between rounded-full bg-primary-500 px-3 py-1.5 font-extrabold text-white"><dt>{{ __('Remaining') }}</dt><dd>{{ money($invoice->balance) }}</dd></div>
            </dl>
        </div>

        <p class="mt-8 text-center text-base font-extrabold text-primary-600">{{ __('Thank you for trusting us!') }} 💛</p>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
