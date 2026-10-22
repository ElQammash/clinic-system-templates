<?php

namespace App\Livewire\Lab;

use App\Actions\Lab\ManageLabRequest;
use App\Enums\LabRequestStatus;
use App\Enums\LabTestType;
use App\Models\LabRequest;
use App\Models\LabTest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Lab & imaging')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    #[Url]
    public string $tab = 'requests';

    #[Url(except: 'requested')]
    public string $status = 'requested';

    #[Url(except: '')]
    public string $search = '';

    // Results form
    public ?int $resultsFor = null;

    public bool $showResults = false;

    /** @var array<int, ?string> */
    public array $results = [];

    public ?string $summary = null;

    /** @var list<TemporaryUploadedFile> */
    public array $files = [];

    // Catalog form
    public string $testName = '';

    public string $testType = 'lab';

    public function mount(): void
    {
        $this->authorize('viewAny', LabRequest::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function openResults(int $id): void
    {
        $request = LabRequest::with('items')->findOrFail($id);
        $this->authorize('update', $request);

        $this->resultsFor = $request->id;
        $this->results = $request->items->mapWithKeys(fn ($item) => [$item->id => $item->result])->all();
        $this->summary = $request->result_summary;
        $this->files = [];
        $this->resetValidation();
        $this->showResults = true;
    }

    public function saveResults(ManageLabRequest $action): void
    {
        $request = LabRequest::findOrFail($this->resultsFor);
        $this->authorize('update', $request);

        $this->validate([
            'results.*' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'files' => ['array', 'max:10'],
            'files.*' => ['file', 'max:'.config('clinic.attachments.max_kb'), 'mimes:'.implode(',', config('clinic.attachments.mimes'))],
        ]);

        $action->complete($request, $this->results, $this->summary, $this->files, auth()->user());

        $this->reset('resultsFor', 'results', 'summary', 'files', 'showResults');
        $this->dispatch('toast', message: __('Results saved to the patient file.'));
    }

    public function cancelRequest(int $id, ManageLabRequest $action): void
    {
        $request = LabRequest::findOrFail($id);
        $this->authorize('update', $request);
        $action->cancel($request);
    }

    public function addTest(): void
    {
        $this->authorize('lab.manage');

        $this->validate([
            'testName' => ['required', 'string', 'max:150'],
            'testType' => ['required', Rule::enum(LabTestType::class)],
        ], attributes: ['testName' => __('Name')]);

        LabTest::create(['name' => $this->testName, 'type' => $this->testType]);
        $this->reset('testName');
    }

    public function toggleTest(int $id): void
    {
        $this->authorize('lab.manage');
        $test = LabTest::findOrFail($id);
        $test->update(['is_active' => ! $test->is_active]);
    }

    public function render()
    {
        $user = auth()->user();

        $requests = LabRequest::query()
            ->with(['patient', 'doctor', 'items'])
            ->when(LabRequestStatus::tryFrom($this->status), fn ($q, $s) => $q->where('status', $s->value))
            ->whereHas('patient', fn (Builder $q) => $q->withTrashed()->medicallyVisibleTo($user)->search($this->search))
            ->latest('requested_at')
            ->paginate(20);

        return view('livewire.lab.index', [
            'requests' => $requests,
            'statuses' => LabRequestStatus::cases(),
            'catalog' => $this->tab === 'catalog' ? LabTest::orderBy('type')->orderBy('name')->get() : collect(),
            'editing' => $this->resultsFor ? LabRequest::with('items', 'patient')->find($this->resultsFor) : null,
            'canManage' => $user->can('lab.manage'),
        ]);
    }
}
