@php use App\Support\Format; @endphp
<div>
    <x-page-header emoji="box" :title="__('Inventory')" :subtitle="__('Consumables and medical supplies.')">
        <x-slot:actions>
            @can('inventory.manage')
                <button type="button" wire:click="edit" class="btn btn-primary"><x-icon name="plus" class="size-5" /> {{ __('Add item') }}</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search items') }}" class="form-control flex-1">
            <label class="flex items-center gap-2 text-sm text-stone-600">
                <input type="checkbox" wire:model.live="lowOnly" class="form-check"> {{ __('Low stock only') }}
                @if ($lowCount)<x-badge color="rose">{{ Format::number($lowCount) }}</x-badge>@endif
            </label>
        </div>
        <table class="table-clean">
            <thead><tr><th>{{ __('Item') }}</th><th class="text-center">{{ __('In stock') }}</th><th class="hidden md:table-cell">{{ __('Last movements') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($items as $item)
                    <tr wire:key="inv-{{ $item->id }}" @class(['opacity-50' => ! $item->is_active])>
                        <td>
                            <span class="font-medium text-ink">{{ $item->name }}</span>
                            @if ($item->isLow())<x-badge color="rose" class="ms-2">{{ __('Low stock') }}</x-badge>@endif
                            <br><span class="text-xs text-muted">{{ __('Reorder at :qty', ['qty' => Format::number($item->reorder_level)]) }} · {{ money($item->unit_cost) }} / {{ $item->unit }}</span>
                        </td>
                        <td class="text-center"><span @class(['text-lg font-bold', 'text-rose-600' => $item->isLow(), 'text-ink' => ! $item->isLow()])>{{ Format::number($item->quantity) }}</span> <span class="text-xs text-muted">{{ $item->unit }}</span></td>
                        <td class="hidden text-xs text-muted md:table-cell">
                            @foreach ($item->movements as $m)
                                <span class="me-2 whitespace-nowrap {{ $m->quantity > 0 ? 'text-emerald-700' : 'text-rose-600' }}" dir="ltr">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</span>
                            @endforeach
                        </td>
                        <td class="text-end whitespace-nowrap">
                            @can('inventory.manage')
                                <button type="button" wire:click="openMove({{ $item->id }}, 'in')" class="btn btn-ghost btn-sm text-emerald-700" title="{{ __('Received') }}"><x-icon name="arrow-in" class="size-5" /></button>
                                <button type="button" wire:click="openMove({{ $item->id }}, 'out')" class="btn btn-ghost btn-sm text-rose-600" title="{{ __('Used') }}"><x-icon name="arrow-out" class="size-5" /></button>
                                <button type="button" wire:click="openMove({{ $item->id }}, 'adjust')" class="btn btn-ghost btn-sm">{{ __('Count') }}</button>
                                <button type="button" wire:click="edit({{ $item->id }})" class="btn btn-ghost btn-sm"><x-icon name="edit" class="size-4" /></button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-14 text-center text-muted">{{ __('No items yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($moving)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/40 p-0 sm:items-center sm:p-6" wire:key="move-{{ $moving->id }}">
            <form wire:submit="move" class="w-full max-w-sm space-y-4 rounded-t-3xl bg-white p-6 shadow-(--shadow-lift) sm:rounded-3xl">
                <h2 class="font-semibold text-ink">{{ $moving->name }} <span class="text-sm font-normal text-muted">({{ Format::number($moving->quantity) }} {{ $moving->unit }})</span></h2>
                <div class="flex rounded-xl bg-stone-100 p-1 text-sm font-semibold">
                    @foreach (['in' => __('Received'), 'out' => __('Used'), 'adjust' => __('Stock count')] as $type => $label)
                        <button type="button" wire:click="$set('moveType', '{{ $type }}')" @class(['flex-1 rounded-xl py-1.5', 'bg-white shadow-none' => $moveType === $type, 'text-muted' => $moveType !== $type])>{{ $label }}</button>
                    @endforeach
                </div>
                <x-field :label="$moveType === 'adjust' ? __('Actual quantity on the shelf') : __('Quantity')" for="mv-qty" error="moveQuantity">
                    <input wire:model="moveQuantity" id="mv-qty" type="number" min="0" dir="ltr" class="form-control text-start" autofocus>
                </x-field>
                <x-field :label="__('Reason')" for="mv-reason" error="moveReason"><input wire:model="moveReason" id="mv-reason" class="form-control"></x-field>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('movingId', null)" class="btn btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    @endif

    <x-modal wire:model="showForm" :title="$editingId ? __('Edit item') : __('Add item')" max-width="max-w-lg">
        <form id="item-form" wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('Name')" for="i-name" error="item.name" required class="sm:col-span-2"><input wire:model="item.name" id="i-name" class="form-control"></x-field>
            <x-field :label="__('Unit')" for="i-unit" error="item.unit"><input wire:model="item.unit" id="i-unit" class="form-control" placeholder="{{ __('e.g. box, piece') }}"></x-field>
            <x-field :label="__('Unit cost')" for="i-cost" error="item.unit_cost"><input wire:model="item.unit_cost" id="i-cost" type="number" min="0" step="0.01" dir="ltr" class="form-control text-start"></x-field>
            <x-field :label="__('Reorder level')" for="i-reorder" error="item.reorder_level" :hint="__('Shows a low-stock alert at or below this quantity.')"><input wire:model="item.reorder_level" id="i-reorder" type="number" min="0" dir="ltr" class="form-control text-start"></x-field>
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="item-form" class="btn btn-primary">{{ __('Save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
