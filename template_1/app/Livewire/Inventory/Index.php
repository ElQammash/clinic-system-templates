<?php

namespace App\Livewire\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Models\InventoryItem;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Inventory')]
class Index extends Component
{
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: false)]
    public bool $lowOnly = false;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $item = [];

    // Stock movement
    public ?int $movingId = null;

    public string $moveType = 'out';

    public ?string $moveQuantity = null;

    public ?string $moveReason = null;

    public function mount(): void
    {
        $this->authorize('inventory.view');
    }

    public function edit(?int $id = null): void
    {
        $this->authorize('inventory.manage');
        $model = $id ? InventoryItem::findOrFail($id) : null;

        $this->editingId = $model?->id;
        $this->item = [
            'name' => $model?->name ?? '',
            'unit' => $model?->unit ?? 'قطعة',
            'reorder_level' => $model?->reorder_level ?? 0,
            'unit_cost' => $model?->unit_cost ?? 0,
            'is_active' => $model?->is_active ?? true,
        ];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('inventory.manage');

        $data = $this->validate([
            'item.name' => ['required', 'string', 'max:150'],
            'item.unit' => ['required', 'string', 'max:30'],
            'item.reorder_level' => ['required', 'integer', 'min:0'],
            'item.unit_cost' => ['required', 'numeric', 'min:0'],
            'item.is_active' => ['boolean'],
        ], attributes: ['item.name' => __('Name')])['item'];

        $this->editingId
            ? InventoryItem::findOrFail($this->editingId)->update($data)
            : InventoryItem::create($data);

        $this->showForm = false;
        $this->dispatch('toast', message: __('Item saved.'));
    }

    public function openMove(int $id, string $type): void
    {
        $this->authorize('inventory.manage');
        $this->movingId = $id;
        $this->moveType = in_array($type, ['in', 'out', 'adjust'], true) ? $type : 'out';
        $this->reset('moveQuantity', 'moveReason');
        $this->resetValidation();
    }

    public function move(AdjustStock $action): void
    {
        $this->authorize('inventory.manage');
        $this->validate([
            'moveType' => ['required', Rule::in(['in', 'out', 'adjust'])],
            'moveQuantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'moveReason' => ['nullable', 'string', 'max:150'],
        ], attributes: ['moveQuantity' => __('Quantity')]);

        $action->handle(InventoryItem::findOrFail($this->movingId), $this->moveType, (int) $this->moveQuantity, $this->moveReason, auth()->user());

        $this->movingId = null;
        $this->dispatch('toast', message: __('Stock updated.'));
    }

    public function render()
    {
        return view('livewire.inventory.index', [
            'items' => InventoryItem::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.addcslashes($this->search, '%_').'%'))
                ->when($this->lowOnly, fn ($q) => $q->lowStock())
                ->with(['movements' => fn ($q) => $q->limit(3)])
                ->orderBy('name')->get(),
            'lowCount' => InventoryItem::query()->lowStock()->count(),
            'moving' => $this->movingId ? InventoryItem::find($this->movingId) : null,
        ]);
    }
}
