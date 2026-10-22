<?php

namespace App\Livewire\Patients;

use App\Enums\Gender;
use App\Models\Patient;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Patients')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $gender = '';

    #[Url(except: false)]
    public bool $trashed = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Patient::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'gender', 'trashed'], true)) {
            $this->resetPage();
        }
    }

    public function restore(int $id): void
    {
        $patient = Patient::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $patient);

        $patient->restore();
        $this->dispatch('toast', message: __('Patient restored.'));
    }

    #[On('patient-saved')]
    public function refreshList(): void
    {
        // Re-render to show the saved patient.
    }

    public function render()
    {
        $user = auth()->user();

        $patients = Patient::query()
            ->when($this->trashed && $user->can('patients.delete'), fn ($q) => $q->onlyTrashed())
            ->search($this->search)
            ->when(Gender::tryFrom($this->gender), fn ($q, Gender $g) => $q->where('gender', $g->value))
            ->latest('id')
            ->paginate(20);

        return view('livewire.patients.index', [
            'patients' => $patients,
            'genders' => Gender::options(),
            'totalPatients' => Patient::count(),
        ]);
    }
}
