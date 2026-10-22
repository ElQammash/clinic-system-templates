@php use App\Support\Format; @endphp
<div x-on:open-receipt.window="window.open($event.detail.url ?? $event.detail[0]?.url, '_blank')">
    <nav class="mb-4 flex items-center gap-2 text-sm text-muted">
        <a href="{{ route('invoices.index') }}" wire:navigate class="hover:text-primary-700">{{ __('Invoices') }}</a>
        <span>/</span>
        <span class="text-ink" dir="ltr">{{ $invoice->number }}</span>
    </nav>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="font-mono text-2xl font-bold tracking-tight text-ink" dir="ltr">{{ $invoice->number }}</h1>
            <x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge>
            @if ($invoice->trashed())<x-badge color="rose">{{ __('Deleted') }}</x-badge>@endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('print.invoice', $invoice) }}" target="_blank" class="btn btn-secondary"><x-icon name="print" class="size-4" /> {{ __('Print') }}</a>
            <a href="{{ route('print.invoice', [$invoice, 'format' => 'pdf']) }}" target="_blank" class="btn btn-ghost">PDF</a>
            @can('update', $invoice)
                <a href="{{ route('invoices.edit', $invoice) }}" wire:navigate class="btn btn-secondary"><x-icon name="edit" class="size-4" /> {{ __('Edit') }}</a>
            @endcan
            @if ($invoice->balance > 0)
                @can('pay', $invoice)
                    <button type="button" wire:click="openPayment" class="btn btn-primary"><x-icon name="cash" class="size-4" /> {{ __('Take payment') }}</button>
                @endcan
            @endif
        </div>
    </div>

    @if ($invoice->isVoid())
        <div class="mb-6 rounded-2xl border border-stone-300 bg-stone-100 px-4 py-3 text-sm text-stone-700">
            <x-icon name="void" class="inline size-4" /> {{ __('Voided on :date', ['date' => Format::dateTime($invoice->voided_at)]) }} — {{ $invoice->void_reason }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <section class="card overflow-hidden">
                <div class="grid gap-4 border-b border-line p-5 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted">{{ __('Patient') }}</p>
                        <a href="{{ route('patients.show', $invoice->patient) }}" wire:navigate class="font-semibold text-ink hover:text-primary-700">{{ $invoice->patient->name }}</a>
                        <p class="text-xs text-muted ltr-nums">#{{ $invoice->patient->file_number }}</p>
                    </div>
                    <div><p class="text-xs text-muted">{{ __('Date') }}</p><p class="font-semibold">{{ Format::dateTime($invoice->issued_at) }}</p></div>
                    <div><p class="text-xs text-muted">{{ __('Doctor') }}</p><p class="font-semibold">{{ $invoice->doctor?->displayName() ?? '—' }}</p></div>
                </div>
                <table class="table-clean">
                    <thead><tr><th>{{ __('Description') }}</th><th class="text-center">{{ __('Quantity') }}</th><th class="text-end">{{ __('Price') }}</th><th class="text-end">{{ __('Total') }}</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td class="font-medium text-ink">{{ $item->description }}@if ($item->discount > 0)<span class="ms-2 text-xs text-emerald-700">(− {{ money($item->discount) }})</span>@endif</td>
                                <td class="text-center">{{ Format::number($item->quantity) }}</td>
                                <td class="text-end">{{ money($item->unit_price) }}</td>
                                <td class="text-end font-semibold">{{ money($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($invoice->notes)
                    <p class="border-t border-line px-5 py-3 text-sm text-stone-600">{{ $invoice->notes }}</p>
                @endif
            </section>

            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 font-semibold text-ink">{{ __('Payments') }}</h2>
                <ul class="divide-y divide-line">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex flex-wrap items-center gap-4 px-5 py-3" wire:key="pay-{{ $payment->id }}">
                            <span class="grid size-9 place-items-center rounded-full bg-emerald-50 text-emerald-700"><x-icon name="{{ $payment->method->value === 'cash' ? 'cash' : 'card' }}" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-ink">{{ money($payment->amount) }} <span class="text-xs font-normal text-muted">· {{ $payment->method->label() }} @if ($payment->reference)· <span dir="ltr">{{ $payment->reference }}</span>@endif</span></p>
                                <p class="text-xs text-muted"><span dir="ltr">{{ $payment->number }}</span> · {{ Format::dateTime($payment->paid_at) }} · {{ $payment->receiver?->name }}</p>
                            </div>
                            <a href="{{ route('print.receipt', $payment) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="receipt" class="size-4" /> {{ __('Receipt') }}</a>
                            @can('payments.delete')
                                <button type="button" wire:click="deletePayment({{ $payment->id }})" wire:confirm="{{ __('Delete this payment? This is recorded in the activity log.') }}" class="btn btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            @endcan
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-muted">{{ __('No payments yet.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="space-y-4">
            <div class="card space-y-3 p-5 text-sm">
                <div class="flex justify-between"><span class="text-muted">{{ __('Subtotal') }}</span><span class="font-semibold">{{ money($invoice->subtotal) }}</span></div>
                @if ($invoice->discount_amount > 0)
                    <div class="flex justify-between text-emerald-700"><span>{{ __('Discount') }} @if ($invoice->discount_type->value === 'percent')({{ Format::number($invoice->discount_value, fmod($invoice->discount_value, 1) ? 1 : 0) }}%)@endif</span><span>− {{ money($invoice->discount_amount) }}</span></div>
                @endif
                <div class="flex justify-between border-t border-line pt-3 text-base"><span class="font-semibold">{{ __('Total') }}</span><span class="font-bold">{{ money($invoice->total) }}</span></div>
                <div class="flex justify-between"><span class="text-muted">{{ __('Paid') }}</span><span class="font-semibold text-emerald-700">{{ money($invoice->paid_amount) }}</span></div>
                <div class="flex justify-between rounded-xl bg-rose-50 px-3 py-2"><span class="font-semibold text-rose-700">{{ __('Remaining') }}</span><span class="font-bold text-rose-700">{{ money($invoice->balance) }}</span></div>
            </div>

            @if ($patientBalance > $invoice->balance)
                <div class="card p-4 text-sm">
                    <p class="text-muted">{{ __('Total owed by this patient') }}</p>
                    <p class="mt-1 text-lg font-bold text-rose-600">{{ money($patientBalance) }}</p>
                </div>
            @endif

            <div class="card space-y-2 p-4 text-xs text-muted">
                <p>{{ __('Created by :name', ['name' => $invoice->creator?->name ?? '—']) }}</p>
                @error('void') <p class="form-error">{{ $message }}</p> @enderror
                @error('delete') <p class="form-error">{{ $message }}</p> @enderror
                <div class="flex flex-wrap gap-2 pt-1">
                    @can('void', $invoice)
                        <button type="button" wire:click="$set('showVoid', true)" class="btn btn-ghost btn-sm text-stone-600"><x-icon name="void" class="size-4" /> {{ __('Void invoice') }}</button>
                    @endcan
                    @can('delete', $invoice)
                        @unless ($invoice->trashed())
                            <button type="button" wire:click="delete" wire:confirm="{{ __('Delete this invoice? This is recorded in the activity log.') }}" class="btn btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /> {{ __('Delete') }}</button>
                        @endunless
                    @endcan
                </div>
            </div>
        </aside>
    </div>

    <x-modal wire:model="showPayment" :title="__('Take payment')" max-width="max-w-md">
        <form id="pay-form" wire:submit="pay" class="space-y-4">
            <x-field :label="__('Amount')" for="pay-amount" error="amount" :hint="__('Remaining: :amount', ['amount' => money($invoice->balance)])">
                <input wire:model="amount" id="pay-amount" type="number" min="0.01" step="0.01" dir="ltr" class="form-control text-start text-lg font-bold">
            </x-field>
            <div>
                <p class="form-label">{{ __('Payment method') }}</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($methods as $value => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-xl border border-stone-300 px-3 py-2.5 text-sm font-medium transition has-checked:border-primary-500 has-checked:bg-primary-50 has-checked:text-primary-800">
                            <input type="radio" wire:model.live="method" value="{{ $value }}" class="sr-only"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            @if ($method !== 'cash')
                <x-field :label="__('Reference / transaction number')" for="pay-ref" error="reference">
                    <input wire:model="reference" id="pay-ref" dir="ltr" class="form-control text-start">
                </x-field>
            @endif
            <x-field :label="__('Notes')" for="pay-notes" error="paymentNotes"><input wire:model="paymentNotes" id="pay-notes" class="form-control"></x-field>
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="pay-form" class="btn btn-primary" wire:loading.attr="disabled" wire:target="pay"><x-icon name="check" class="size-4" /> {{ __('Record payment & print receipt') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-modal wire:model="showVoid" :title="__('Void invoice')" max-width="max-w-md">
        <form id="void-form" wire:submit="void" class="space-y-3">
            <p class="text-sm text-muted">{{ __('The invoice stays in the records but is removed from revenue and the patient balance.') }}</p>
            <x-field :label="__('Reason')" for="void-reason" error="voidReason" required><input wire:model="voidReason" id="void-reason" class="form-control"></x-field>
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="void-form" class="btn btn-danger">{{ __('Void invoice') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
