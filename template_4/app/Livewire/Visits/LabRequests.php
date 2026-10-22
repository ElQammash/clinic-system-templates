<?php

namespace App\Livewire\Visits;

use App\Actions\Lab\ManageLabRequest;
use App\Enums\LabTestType;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\Visit;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Lab & imaging requests made during a visit (module "lab").
 */
class LabRequests extends Component
{
    #[Locked]
    public int $visitId;

    /** @var list<int> */
    public array $selectedTests = [];

    public string $otherTests = '';

    public ?string $notes = null;

    public bool $showForm = false;

    public function mount(Visit $visit): void
    {
        $this->visitId = $visit->id;
    }

    public function create(ManageLabRequest $action): void
    {
        $this->authorize('create', LabRequest::class);
        $visit = Visit::with('patient')->findOrFail($this->visitId);
        $this->authorize('update', $visit);

        $this->validate([
            'selectedTests' => ['array'],
            'selectedTests.*' => ['integer'],
            'otherTests' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $action->create($visit->patient, $visit, auth()->user(), $this->selectedTests, preg_split('/[\n,،]+/u', $this->otherTests), $this->notes);

        $this->reset('selectedTests', 'otherTests', 'notes', 'showForm');
        $this->dispatch('toast', message: __('Lab request created.'));
    }

    public function render()
    {
        return view('livewire.visits.lab-requests', [
            'requests' => LabRequest::query()->where('visit_id', $this->visitId)->with('items')->latest()->get(),
            'tests' => LabTest::query()->where('is_active', true)->orderBy('name')->get()->groupBy(fn (LabTest $t) => $t->type->value),
            'types' => LabTestType::cases(),
            'canCreate' => auth()->user()->can('create', LabRequest::class),
        ]);
    }
}
