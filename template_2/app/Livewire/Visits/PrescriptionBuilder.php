<?php

namespace App\Livewire\Visits;

use App\Actions\Prescriptions\SavePrescription;
use App\Models\Medicine;
use App\Models\PrescriptionTemplate;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PrescriptionBuilder extends Component
{
    #[Locked]
    public int $visitId;

    /** @var list<array{medicine_id: ?int, medicine_name: string, dose: ?string, frequency: ?string, duration: ?string, instructions: ?string}> */
    public array $items = [];

    public ?string $notes = null;

    public string $medicineSearch = '';

    public bool $showSaveTemplate = false;

    public string $templateName = '';

    public bool $templateShared = false;

    public function mount(Visit $visit): void
    {
        $this->visitId = $visit->id;
        $prescription = $visit->prescription()->with('items')->first();

        $this->notes = $prescription?->notes;
        $this->items = $prescription?->items->map(fn ($item) => [
            'medicine_id' => $item->medicine_id,
            'medicine_name' => $item->medicine_name,
            'dose' => $item->dose,
            'frequency' => $item->frequency,
            'duration' => $item->duration,
            'instructions' => $item->instructions,
        ])->all() ?? [];
    }

    protected function visit(): Visit
    {
        return Visit::findOrFail($this->visitId);
    }

    #[Computed]
    public function medicineResults(): Collection
    {
        return mb_strlen(trim($this->medicineSearch)) >= 2
            ? Medicine::query()->active()->search($this->medicineSearch)->limit(8)->get()
            : collect();
    }

    public function addMedicine(?int $id = null): void
    {
        $medicine = $id ? Medicine::find($id) : null;

        $this->items[] = [
            'medicine_id' => $medicine?->id,
            'medicine_name' => $medicine?->displayName() ?? trim($this->medicineSearch),
            'dose' => $medicine?->default_dose,
            'frequency' => $medicine?->default_frequency,
            'duration' => $medicine?->default_duration,
            'instructions' => null,
        ];

        $this->medicineSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function moveItem(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (isset($this->items[$index], $this->items[$target])) {
            [$this->items[$index], $this->items[$target]] = [$this->items[$target], $this->items[$index]];
        }
    }

    public function applyTemplate(int $id): void
    {
        $template = PrescriptionTemplate::query()->availableTo(auth()->user())->with('items')->findOrFail($id);

        foreach ($template->items as $item) {
            $this->items[] = [
                'medicine_id' => $item->medicine_id,
                'medicine_name' => $item->medicine_name,
                'dose' => $item->dose,
                'frequency' => $item->frequency,
                'duration' => $item->duration,
                'instructions' => $item->instructions,
            ];
        }

        if ($template->notes && blank($this->notes)) {
            $this->notes = $template->notes;
        }
    }

    public function save(SavePrescription $action): void
    {
        $this->authorize('prescribe', $this->visit());

        $this->validate([
            'items' => ['array', 'max:30'],
            'items.*.medicine_name' => ['required', 'string', 'max:150'],
            'items.*.dose' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['items.*.medicine_name' => __('Medicine')]);

        $action->handle($this->visit(), $this->items, $this->notes, auth()->user());

        $this->dispatch('prescription-saved');
        $this->dispatch('toast', message: __('Prescription saved.'));
    }

    public function saveAsTemplate(): void
    {
        $this->authorize('prescription_templates.manage');

        $this->validate([
            'templateName' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
        ], attributes: ['templateName' => __('Template name')]);

        DB::transaction(function () {
            $template = PrescriptionTemplate::create([
                'doctor_id' => $this->templateShared ? null : auth()->id(),
                'name' => $this->templateName,
                'notes' => $this->notes,
            ]);

            foreach ($this->items as $i => $item) {
                $template->items()->create([...$item, 'sort' => $i]);
            }
        });

        $this->reset('showSaveTemplate', 'templateName', 'templateShared');
        $this->dispatch('toast', message: __('Template saved.'));
    }

    public function render()
    {
        $visit = $this->visit();

        return view('livewire.visits.prescription-builder', [
            'templates' => PrescriptionTemplate::query()->availableTo(auth()->user())->orderBy('name')->get(),
            'canPrescribe' => auth()->user()->can('prescribe', $visit),
            'prescription' => $visit->prescription()->first(),
            'suggestions' => [
                'dose' => ['قرص واحد', 'نصف قرص', 'كبسولة واحدة', '5 مل', '2.5 مل', '10 مل', 'نقطتين', 'بخة واحدة', 'دهان طبقة رقيقة', 'حقنة واحدة'],
                'frequency' => ['مرة يوميًا', 'مرتين يوميًا', '3 مرات يوميًا', 'كل 6 ساعات', 'كل 8 ساعات', 'كل 12 ساعة', 'عند اللزوم', 'قبل النوم', 'صباحًا', 'مرة أسبوعيًا'],
                'duration' => ['3 أيام', '5 أيام', '7 أيام', '10 أيام', '14 يومًا', 'شهر', 'مستمر'],
                'instructions' => ['بعد الأكل', 'قبل الأكل', 'على معدة فارغة', 'مع كثير من الماء', 'يُرج قبل الاستعمال'],
            ],
        ]);
    }
}
