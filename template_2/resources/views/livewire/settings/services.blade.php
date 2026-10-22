<div>
    <x-page-header :title="__('Services & prices')" :subtitle="__('The price list used on invoices. Changing a price does not affect invoices already issued.')">
        <x-slot:actions>
            <button type="button" wire:click="edit" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add service') }}</button>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @foreach ($services as $category => $group)
            <div class="card overflow-hidden">
                <h2 class="border-b border-line bg-subtle px-5 py-3 text-sm font-semibold text-muted">{{ $category }}</h2>
                <ul class="divide-y divide-line">
                    @foreach ($group as $service)
                        <li class="flex items-center gap-4 px-5 py-3" wire:key="svc-{{ $service->id }}">
                            <span @class(['flex-1', 'font-medium text-ink' => $service->is_active, 'text-muted line-through' => ! $service->is_active])>{{ $service->name() }}</span>
                            <span class="font-semibold">{{ money($service->price) }}</span>
                            <button type="button" wire:click="edit({{ $service->id }})" class="btn btn-ghost btn-sm"><x-icon name="edit" class="size-4" /></button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <x-modal wire:model="showForm" :title="$editingId ? __('Edit service') : __('Add service')" max-width="max-w-lg">
        <form id="svc-form" wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('Name (Arabic)')" for="s-ar" error="service.name_ar" required><input wire:model="service.name_ar" id="s-ar" class="form-control"></x-field>
            <x-field :label="__('Name (English)')" for="s-en" error="service.name_en"><input wire:model="service.name_en" id="s-en" dir="ltr" class="form-control text-start"></x-field>
            <x-field :label="__('Category')" for="s-cat" error="service.category"><input wire:model="service.category" id="s-cat" class="form-control" placeholder="{{ __('e.g. Procedures') }}"></x-field>
            <x-field :label="__('Price')" for="s-price" error="service.price" required><input wire:model="service.price" id="s-price" type="number" min="0" step="0.01" dir="ltr" class="form-control text-start"></x-field>
            <label class="flex items-center gap-2 text-sm text-ink-soft"><input type="checkbox" wire:model="service.is_active" class="form-check"> {{ __('Active') }}</label>
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="svc-form" class="btn btn-primary">{{ __('Save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
