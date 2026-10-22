@php $totals = $this->totals; @endphp
<div>
    <x-page-header emoji="invoice" :title="$invoiceId ? __('Edit invoice') : __('New invoice')" />

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            {{-- Patient --}}
            <section class="card p-5">
                <p class="form-label">{{ __('Patient') }} <span class="text-rose-500">*</span></p>
                @if ($this->patient)
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-primary-200 bg-primary-50/50 px-4 py-3">
                        <div>
                            <p class="font-semibold text-ink">{{ $this->patient->name }}</p>
                            <p class="text-xs text-muted"><span class="ltr-nums">#{{ $this->patient->file_number }}</span> · <span class="ltr-nums">{{ $this->patient->phone }}</span></p>
                        </div>
                        @unless ($invoiceId || $appointmentId || $visitId)
                            <button type="button" wire:click="$set('patientId', null)" class="btn btn-ghost btn-sm">{{ __('Change') }}</button>
                        @endunless
                    </div>
                @else
                    <input wire:model.live.debounce.300ms="patientSearch" type="search" autocomplete="off" placeholder="{{ __('Search by name, phone or file number') }}" @class(['form-control', 'is-invalid' => $errors->has('patientId')])>
                    @if (mb_strlen($patientSearch) >= 2)
                        <ul class="mt-2 divide-y divide-line rounded-2xl border border-line">
                            @forelse ($this->patientResults as $result)
                                <li><button type="button" wire:click="selectPatient({{ $result->id }})" class="w-full px-4 py-2.5 text-start text-sm hover:bg-primary-50/60">{{ $result->name }} <span class="text-xs text-muted ltr-nums">#{{ $result->file_number }}</span></button></li>
                            @empty
                                <li class="px-4 py-3 text-sm text-muted">{{ __('No patients found.') }}</li>
                            @endforelse
                        </ul>
                    @endif
                    @error('patientId') <p class="form-error">{{ $message }}</p> @enderror
                @endif
            </section>

            {{-- Items --}}
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 class="font-semibold text-ink">{{ __('Items') }}</h2>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" x-on:click="open = !open" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> {{ __('Add service') }}</button>
                        <div x-show="open" x-cloak x-on:click.outside="open = false" class="absolute end-0 z-20 mt-1 max-h-80 w-72 overflow-y-auto rounded-2xl border border-line bg-white py-1 shadow-(--shadow-lift)">
                            @foreach ($services as $service)
                                <button type="button" wire:click="addItem({{ $service->id }})" x-on:click="open = false" class="flex w-full items-center justify-between px-4 py-2 text-start text-sm hover:bg-primary-50">
                                    <span>{{ $service->name() }}</span><span class="text-xs font-semibold text-muted">{{ money($service->price) }}</span>
                                </button>
                            @endforeach
                            <button type="button" wire:click="addItem" x-on:click="open = false" class="block w-full border-t border-line px-4 py-2 text-start text-sm font-semibold text-primary-700 hover:bg-primary-50">{{ __('Other item') }}</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th class="min-w-56">{{ __('Description') }}</th>
                                <th class="w-24">{{ __('Quantity') }}</th>
                                <th class="w-32">{{ __('Price') }}</th>
                                <th class="w-28">{{ __('Discount') }}</th>
                                <th class="w-28 text-end">{{ __('Total') }}</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($items as $i => $item)
                                <tr wire:key="item-{{ $i }}">
                                    <td><input wire:model="items.{{ $i }}.description" @class(['form-control py-2', 'is-invalid' => $errors->has("items.$i.description")])></td>
                                    <td><input wire:model.live.debounce.300ms="items.{{ $i }}.quantity" type="number" min="1" dir="ltr" class="form-control py-2 text-center"></td>
                                    <td><input wire:model.live.debounce.300ms="items.{{ $i }}.unit_price" type="number" min="0" step="0.01" dir="ltr" @class(['form-control py-2 text-start', 'is-invalid' => $errors->has("items.$i.unit_price")])></td>
                                    <td><input wire:model.live.debounce.300ms="items.{{ $i }}.discount" type="number" min="0" step="0.01" dir="ltr" class="form-control py-2 text-start"></td>
                                    <td class="text-end font-semibold">{{ money($totals['lines'][$i] ?? 0) }}</td>
                                    <td><button type="button" wire:click="removeItem({{ $i }})" class="text-stone-400 hover:text-rose-600" aria-label="{{ __('Remove') }}"><x-icon name="close" class="size-4" /></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('items') <p class="form-error px-5 pb-4">{{ $message }}</p> @enderror
            </section>

            <section class="card p-5">
                <x-field :label="__('Notes')" for="inv-notes" error="notes">
                    <textarea wire:model="notes" id="inv-notes" rows="2" class="form-control"></textarea>
                </x-field>
            </section>
        </div>

        {{-- Summary --}}
        <aside class="card h-fit space-y-4 p-5 lg:sticky lg:top-20">
            <h2 class="font-semibold text-ink">{{ __('Summary') }}</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('Subtotal') }}</dt><dd class="font-semibold">{{ money($totals['subtotal']) }}</dd></div>
            </dl>

            <div>
                <p class="form-label">{{ __('Discount') }}</p>
                <div class="flex gap-2">
                    <input wire:model.live.debounce.300ms="discountValue" type="number" min="0" step="0.01" dir="ltr" @class(['form-control text-start', 'is-invalid' => $errors->has('discountValue')])>
                    <div class="flex rounded-xl bg-stone-100 p-1 text-sm font-semibold">
                        <button type="button" wire:click="$set('discountType', 'fixed')" @class(['rounded-xl px-3', 'bg-white shadow-none' => $discountType === 'fixed', 'text-muted' => $discountType !== 'fixed'])>{{ settings()->localized('currency_symbol') }}</button>
                        <button type="button" wire:click="$set('discountType', 'percent')" @class(['rounded-xl px-3', 'bg-white shadow-none' => $discountType === 'percent', 'text-muted' => $discountType !== 'percent'])>%</button>
                    </div>
                </div>
                @error('discountValue') <p class="form-error">{{ $message }}</p> @enderror
                @if ($totals['discount_amount'] > 0)
                    <p class="form-hint">− {{ money($totals['discount_amount']) }}</p>
                @endif
            </div>

            <div class="flex items-baseline justify-between border-t border-line pt-4">
                <span class="font-semibold text-ink">{{ __('Total') }}</span>
                <span class="text-2xl font-bold text-primary-700">{{ money($totals['total']) }}</span>
            </div>

            @error('invoice') <p class="form-error">{{ $message }}</p> @enderror
            <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled" wire:target="save"><x-icon name="check" class="size-4" /> {{ $invoiceId ? __('Save changes') : __('Create invoice') }}</button>
        </aside>
    </form>
</div>
