<?php

namespace App\Livewire\Visits;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Visits')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    /** 0 = all doctors the user may see */
    #[Url(as: 'doctor')]
    public int $doctorId = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Visit::class);

        if (auth()->user()->isDoctor() && ! auth()->user()->can('records.view_all')) {
            $this->doctorId = $this->doctorId ?: auth()->id();
        }
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $visits = Visit::query()
            ->with(['patient', 'doctor', 'prescription'])
            ->whereHas('patient', fn (Builder $q) => $q->withTrashed()->medicallyVisibleTo($user)->search($this->search))
            ->when($this->doctorId, fn ($q) => $q->where('doctor_id', $this->doctorId))
            ->when($this->from, fn ($q) => $q->whereDate('visited_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('visited_at', '<=', $this->to))
            ->latest('visited_at')
            ->paginate(20);

        return view('livewire.visits.index', [
            'visits' => $visits,
            'doctors' => User::query()->doctors()->orderBy('name')->get(),
        ]);
    }
}
