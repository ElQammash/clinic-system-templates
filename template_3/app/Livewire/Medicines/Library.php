<?php

namespace App\Livewire\Medicines;

use App\Enums\MedicineForm;
use App\Enums\Permission;
use App\Models\Medicine;
use App\Models\PrescriptionTemplate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Medicines')]
class Library extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'medicines';

    #[Url(except: '')]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $medicine = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::MedicinesManage->value) || auth()->user()->can(Permission::PrescriptionTemplatesManage->value), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(?int $id = null): void
    {
        $this->authorize(Permission::MedicinesManage->value);
        $model = $id ? Medicine::findOrFail($id) : null;

        $this->editingId = $model?->id;
        $this->medicine = [
            'name' => $model?->name ?? '',
            'generic_name' => $model?->generic_name,
            'form' => $model?->form?->value,
            'strength' => $model?->strength,
            'default_dose' => $model?->default_dose,
            'default_frequency' => $model?->default_frequency,
            'default_duration' => $model?->default_duration,
            'notes' => $model?->notes,
            'is_active' => $model?->is_active ?? true,
        ];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize(Permission::MedicinesManage->value);

        $data = $this->validate([
            'medicine.name' => ['required', 'string', 'max:150'],
            'medicine.generic_name' => ['nullable', 'string', 'max:150'],
            'medicine.form' => ['nullable', Rule::enum(MedicineForm::class)],
            'medicine.strength' => ['nullable', 'string', 'max:50'],
            'medicine.default_dose' => ['nullable', 'string', 'max:100'],
            'medicine.default_frequency' => ['nullable', 'string', 'max:100'],
            'medicine.default_duration' => ['nullable', 'string', 'max:100'],
            'medicine.notes' => ['nullable', 'string', 'max:255'],
            'medicine.is_active' => ['boolean'],
        ], attributes: ['medicine.name' => __('Name')])['medicine'];

        $data['form'] = $data['form'] ?: null;

        $this->editingId
            ? Medicine::findOrFail($this->editingId)->update($data)
            : Medicine::create($data);

        $this->showForm = false;
        $this->dispatch('toast', message: __('Medicine saved.'));
    }

    public function toggle(int $id): void
    {
        $this->authorize(Permission::MedicinesManage->value);
        $medicine = Medicine::findOrFail($id);
        $medicine->update(['is_active' => ! $medicine->is_active]);
    }

    public function deleteTemplate(int $id): void
    {
        $this->authorize(Permission::PrescriptionTemplatesManage->value);
        $template = PrescriptionTemplate::findOrFail($id);

        // Doctors manage their own templates; shared ones need the admin.
        abort_unless($template->doctor_id === auth()->id() || auth()->user()->isAdmin(), 403);

        $template->delete();
        $this->dispatch('toast', message: __('Template deleted.'));
    }

    public function render()
    {
        return view('livewire.medicines.library', [
            'medicines' => $this->tab === 'medicines'
                ? Medicine::query()->search($this->search)->when(! $this->search, fn ($q) => $q->orderBy('name'))->paginate(25)
                : collect(),
            'templates' => $this->tab === 'templates'
                ? PrescriptionTemplate::query()->availableTo(auth()->user())->with('items', 'doctor')->orderBy('name')->get()
                : collect(),
            'forms' => MedicineForm::options(),
        ]);
    }
}
