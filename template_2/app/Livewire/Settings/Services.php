<?php

namespace App\Livewire\Settings;

use App\Enums\Permission;
use App\Models\Service;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Services & prices')]
class Services extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $service = [];

    public function mount(): void
    {
        $this->authorize(Permission::ServicesManage->value);
    }

    public function edit(?int $id = null): void
    {
        $this->authorize(Permission::ServicesManage->value);
        $model = $id ? Service::findOrFail($id) : null;

        $this->editingId = $model?->id;
        $this->service = [
            'name_ar' => $model?->name_ar ?? '',
            'name_en' => $model?->name_en,
            'category' => $model?->category,
            'price' => $model?->price ?? 0,
            'is_active' => $model?->is_active ?? true,
        ];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize(Permission::ServicesManage->value);

        $data = $this->validate([
            'service.name_ar' => ['required', 'string', 'max:150'],
            'service.name_en' => ['nullable', 'string', 'max:150'],
            'service.category' => ['nullable', 'string', 'max:50'],
            'service.price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'service.is_active' => ['boolean'],
        ], attributes: ['service.name_ar' => __('Name (Arabic)'), 'service.price' => __('Price')])['service'];

        if ($this->editingId) {
            $before = Service::findOrFail($this->editingId);
            activity('settings')->performedOn($before)->event('updated')
                ->withProperties(['old_price' => $before->price, 'new_price' => (float) $data['price']])->log('service price updated');
            $before->update($data);
        } else {
            Service::create($data + ['sort' => (int) Service::max('sort') + 1]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: __('Service saved.'));
    }

    public function render()
    {
        return view('livewire.settings.services', [
            'services' => Service::query()->orderBy('category')->orderBy('sort')->get()->groupBy(fn (Service $s) => $s->category ?: __('Other')),
        ]);
    }
}
