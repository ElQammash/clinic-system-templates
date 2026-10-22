@php
    use App\Support\Format;
    $patient = $invoice->patient;
@endphp
<x-print-layout :title="$title" size="a4">
    <div class="flex min-h-[276mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $invoice->doctor])

        <div class="mt-5 flex items-end justify-between gap-6">
            <div>
                <p class="cap">{{ __('Invoice') }}</p>
                <p class="font-mono text-2xl font-bold" dir="ltr">{{ $invoice->number }}</p>
            </div>
            @if ($invoice->isVoid())<p class="border-2 border-rose-600 px-3 py-1 font-mono text-sm font-bold tracking-widest text-rose-600 uppercase">{{ __('Void') }}</p>@endif
        </div>

        <div class="mt-3 grid grid-cols-4 border border-neutral-900 text-[11px]">
            <div class="cell border-e"><p class="cap">{{ __('Date') }}</p><p class="font-mono font-semibold" dir="ltr">{{ $invoice->issued_at->format('Y-m-d') }}</p></div>
            <div class="cell border-e"><p class="cap">{{ __('Patient') }}</p><p class="font-semibold">{{ $patient->name }}</p></div>
            <div class="cell border-e"><p class="cap">{{ __('File no.') }}</p><p class="font-mono font-semibold" dir="ltr">{{ $patient->file_number }}</p></div>
            <div class="cell"><p class="cap">{{ __('Phone') }}</p><p class="font-mono" dir="ltr">{{ $patient->phone ?: '—' }}</p></div>
        </div>

        <table class="mt-5 w-full text-[12px]">
            <thead>
                <tr class="bg-neutral-950 text-white">
                    <th class="w-10 px-2.5 py-1.5 text-start font-mono text-[10px] font-semibold">#</th>
                    <th class="px-2.5 py-1.5 text-start text-[10px] font-semibold tracking-wider uppercase">{{ __('Description') }}</th>
                    <th class="px-2.5 py-1.5 text-center text-[10px] font-semibold tracking-wider uppercase">{{ __('Quantity') }}</th>
                    <th class="px-2.5 py-1.5 text-end text-[10px] font-semibold tracking-wider uppercase">{{ __('Price') }}</th>
                    <th class="px-2.5 py-1.5 text-end text-[10px] font-semibold tracking-wider uppercase">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr class="border-b border-neutral-300">
                        <td class="px-2.5 py-1.5 font-mono text-[10px] text-neutral-500">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-2.5 py-1.5">{{ $item->description }}@if ($item->discount > 0)<span class="text-[10px] text-neutral-500"> (− {{ money($item->discount) }})</span>@endif</td>
                        <td class="px-2.5 py-1.5 text-center font-mono">{{ Format::number($item->quantity) }}</td>
                        <td class="px-2.5 py-1.5 text-end font-mono">{{ money($item->unit_price) }}</td>
                        <td class="px-2.5 py-1.5 text-end font-mono font-semibold">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 flex justify-between gap-6">
            <div class="text-[10px] text-neutral-600">
                @if ($invoice->payments->isNotEmpty())
                    <p class="cap mb-1">{{ __('Payments') }}</p>
                    @foreach ($invoice->payments as $payment)
                        <p class="font-mono" dir="ltr">{{ $payment->paid_at->format('Y-m-d') }} · {{ $payment->number }} · <span dir="auto">{{ $payment->method->label() }}</span> · {{ money($payment->amount) }}</p>
                    @endforeach
                @endif
                @if ($invoice->notes)<p class="mt-3 max-w-sm text-neutral-700">{{ $invoice->notes }}</p>@endif
            </div>
            <dl class="w-72 border border-neutral-900 text-[12px]">
                <div class="flex justify-between px-3 py-1"><dt class="text-neutral-600">{{ __('Subtotal') }}</dt><dd class="font-mono">{{ money($invoice->subtotal) }}</dd></div>
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between px-3 py-1"><dt class="text-neutral-600">{{ __('Discount') }}</dt><dd class="font-mono">− {{ money($invoice->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-neutral-900 px-3 py-1.5 text-sm font-bold"><dt>{{ __('Total') }}</dt><dd class="font-mono">{{ money($invoice->total) }}</dd></div>
                <div class="flex justify-between px-3 py-1"><dt class="text-neutral-600">{{ __('Paid') }}</dt><dd class="font-mono">{{ money($invoice->paid_amount) }}</dd></div>
                <div class="flex justify-between bg-neutral-950 px-3 py-1.5 font-bold text-white"><dt>{{ __('Remaining') }}</dt><dd class="font-mono">{{ money($invoice->balance) }}</dd></div>
            </dl>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('invoice_footer')])
    </div>
</x-print-layout>
