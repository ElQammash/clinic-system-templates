@php use App\Support\Format; @endphp
<div>
    <x-page-header :title="__('Invoices')" :subtitle="__('Bills, payments and what patients still owe.')">
        <x-slot:actions>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create') }}" wire:navigate class="btn btn-primary"><x-icon name="plus" class="size-5" /> {{ __('New invoice') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($showTotals)
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5">
                <p class="text-sm text-muted">{{ __('Collected today') }}</p>
                <p class="mt-2 text-2xl font-bold text-ink">{{ money($today->sum('total')) }}</p>
                <p class="mt-1 text-xs text-muted">{{ trans_choice(':count payment|:count payments', $today->sum('count'), ['count' => Format::number($today->sum('count'))]) }}</p>
            </div>
            <div class="card p-5 lg:col-span-2">
                <p class="text-sm text-muted">{{ __('Today by payment method') }}</p>
                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2">
                    @forelse ($today as $method => $row)
                        <div><p class="text-xs text-muted">{{ $methods[$method] }}</p><p class="font-semibold text-ink">{{ money($row['total']) }}</p></div>
                    @empty
                        <p class="text-sm text-muted">{{ __('No payments yet today.') }}</p>
                    @endforelse
                </div>
            </div>
            <div class="card border-rose-100 bg-rose-50/40 p-5">
                <p class="text-sm text-rose-700">{{ __('Outstanding (all patients)') }}</p>
                <p class="mt-2 text-2xl font-bold text-rose-700">{{ money($outstanding) }}</p>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-muted" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Invoice number, patient name or phone') }}" class="form-control ps-10">
            </div>
            <select wire:model.live="status" class="form-control lg:w-44">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
            </select>
            <div class="flex items-center gap-2">
                <input wire:model.live="from" type="date" class="form-control w-40" aria-label="{{ __('From') }}">
                <span class="text-muted">—</span>
                <input wire:model.live="to" type="date" class="form-control w-40" aria-label="{{ __('To') }}">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>{{ __('Invoice') }}</th>
                        <th>{{ __('Patient') }}</th>
                        <th class="hidden md:table-cell">{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th class="hidden text-end sm:table-cell">{{ __('Remaining') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($invoices as $invoice)
                        <tr wire:key="inv-{{ $invoice->id }}" class="cursor-pointer" onclick="Livewire.navigate('{{ route('invoices.show', $invoice) }}')">
                            <td><span class="font-mono text-xs font-semibold text-ink-soft" dir="ltr">{{ $invoice->number }}</span></td>
                            <td><span class="font-medium text-ink">{{ $invoice->patient->name }}</span></td>
                            <td class="hidden text-muted md:table-cell">{{ Format::date($invoice->issued_at) }}</td>
                            <td class="text-end font-semibold">{{ money($invoice->total) }}</td>
                            <td class="hidden text-end sm:table-cell {{ $invoice->balance > 0 ? 'font-semibold text-rose-600' : 'text-muted' }}">{{ money($invoice->balance) }}</td>
                            <td><x-badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-muted">{{ __('No invoices found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
