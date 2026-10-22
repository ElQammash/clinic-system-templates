<?php

namespace App\Livewire\Settings;

use App\Actions\Scheduling\SaveWorkingHours;
use App\Enums\Permission;
use App\Models\BlockedPeriod;
use App\Models\User;
use App\Models\VisitType;
use App\Models\WorkingHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Schedule & visit types')]
class Schedule extends Component
{
    #[Url]
    public string $tab = 'hours';

    /** 0 = clinic default hours */
    #[Url(as: 'doctor')]
    public int $hoursFor = 0;

    /** @var array<int, list<array{from: ?string, to: ?string}>> */
    public array $shifts = [];

    public bool $hasOwnHours = false;

    // Blocked period form
    public ?int $blockDoctorId = null;

    public string $blockFrom = '';

    public string $blockTo = '';

    public ?string $blockReason = null;

    // Visit type form
    public bool $showTypeForm = false;

    public ?int $typeId = null;

    public string $typeNameAr = '';

    public ?string $typeNameEn = null;

    public float|int|string $typePrice = 0;

    public ?string $typeDuration = null;

    public string $typeColor = '#0d9488';

    /** Week days in Egyptian order, Saturday first (Carbon numbering). */
    public const DAYS = [6, 0, 1, 2, 3, 4, 5];

    public function mount(): void
    {
        $this->authorize(Permission::ScheduleManage->value);
        $this->loadShifts();
        $this->blockFrom = today()->toDateString();
        $this->blockTo = today()->toDateString();
    }

    #[Computed]
    public function doctors(): Collection
    {
        return User::query()->doctors()->active()->with('doctorProfile')->orderBy('name')->get();
    }

    public function updatedHoursFor(): void
    {
        $this->loadShifts();
    }

    protected function loadShifts(): void
    {
        $doctorId = $this->hoursFor ?: null;
        $this->hasOwnHours = $doctorId && WorkingHour::where('doctor_id', $doctorId)->exists();

        $rows = WorkingHour::query()
            ->when($this->hasOwnHours, fn ($q) => $q->where('doctor_id', $doctorId), fn ($q) => $q->whereNull('doctor_id'))
            ->orderBy('starts_at')->get();

        $this->shifts = [];
        foreach (self::DAYS as $day) {
            $this->shifts[$day] = $rows->where('day_of_week', $day)
                ->map(fn (WorkingHour $h) => ['from' => $h->startTime(), 'to' => $h->endTime()])
                ->values()->all();
        }
        $this->resetValidation();
    }

    public function addShift(int $day): void
    {
        $last = end($this->shifts[$day]) ?: null;
        $this->shifts[$day][] = $last ? ['from' => $last['to'], 'to' => null] : ['from' => '16:00', 'to' => '22:00'];
    }

    public function removeShift(int $day, int $index): void
    {
        unset($this->shifts[$day][$index]);
        $this->shifts[$day] = array_values($this->shifts[$day]);
    }

    public function copyToAllDays(int $fromDay): void
    {
        foreach (self::DAYS as $day) {
            if ($day !== 5) { // keep Friday as it is
                $this->shifts[$day] = $this->shifts[$fromDay];
            }
        }
    }

    public function saveHours(SaveWorkingHours $action): void
    {
        $this->authorize(Permission::ScheduleManage->value);
        $action->handle($this->hoursFor ?: null, $this->shifts);
        $this->loadShifts();
        $this->dispatch('toast', message: __('Working hours saved.'));
    }

    public function useClinicDefaults(SaveWorkingHours $action): void
    {
        $this->authorize(Permission::ScheduleManage->value);
        if ($this->hoursFor) {
            $action->useClinicDefaults($this->hoursFor);
            $this->loadShifts();
            $this->dispatch('toast', message: __('This doctor now follows the clinic hours.'));
        }
    }

    public function addBlockedPeriod(): void
    {
        $this->authorize(Permission::ScheduleManage->value);

        $data = $this->validate([
            'blockDoctorId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'blockFrom' => ['required', 'date'],
            'blockTo' => ['required', 'date', 'after_or_equal:blockFrom'],
            'blockReason' => ['nullable', 'string', 'max:150'],
        ], attributes: ['blockFrom' => __('From'), 'blockTo' => __('To')]);

        BlockedPeriod::create([
            'doctor_id' => $data['blockDoctorId'] ?: null,
            'starts_at' => $data['blockFrom'].' 00:00:00',
            'ends_at' => Carbon::parse($data['blockTo'])->endOfDay(),
            'reason' => $data['blockReason'],
            'created_by' => auth()->id(),
        ]);

        $this->reset('blockReason');
        $this->dispatch('toast', message: __('Days off saved.'));
    }

    public function deleteBlockedPeriod(int $id): void
    {
        $this->authorize(Permission::ScheduleManage->value);
        BlockedPeriod::findOrFail($id)->delete();
    }

    public function editType(?int $id = null): void
    {
        $this->authorize(Permission::ScheduleManage->value);
        $type = $id ? VisitType::findOrFail($id) : null;

        $this->typeId = $type?->id;
        $this->typeNameAr = $type?->name_ar ?? '';
        $this->typeNameEn = $type?->name_en;
        $this->typePrice = $type?->default_price ?? 0;
        $this->typeDuration = $type?->default_duration ? (string) $type->default_duration : null;
        $this->typeColor = $type?->color ?? '#0d9488';
        $this->resetValidation();
        $this->showTypeForm = true;
    }

    public function saveType(): void
    {
        $this->authorize(Permission::ScheduleManage->value);

        $data = $this->validate([
            'typeNameAr' => ['required', 'string', 'max:100'],
            'typeNameEn' => ['nullable', 'string', 'max:100'],
            'typePrice' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'typeDuration' => ['nullable', 'integer', 'min:5', 'max:480'],
            'typeColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], attributes: ['typeNameAr' => __('Name (Arabic)'), 'typePrice' => __('Price'), 'typeDuration' => __('Duration')]);

        $type = $this->typeId ? VisitType::findOrFail($this->typeId) : new VisitType(['sort' => (int) VisitType::max('sort') + 1]);

        $type->fill([
            'name_ar' => $data['typeNameAr'],
            'name_en' => $data['typeNameEn'],
            'default_price' => (float) $data['typePrice'],
            'default_duration' => $data['typeDuration'] ?: null,
            'color' => strtolower($data['typeColor']),
        ])->save();

        $this->showTypeForm = false;
        $this->dispatch('toast', message: __('Visit type saved.'));
    }

    public function toggleType(int $id): void
    {
        $this->authorize(Permission::ScheduleManage->value);
        $type = VisitType::findOrFail($id);
        $type->update(['is_active' => ! $type->is_active]);
    }

    public function render()
    {
        return view('livewire.settings.schedule', [
            // 2024-01-07 is a Sunday (day 0), so adding the Carbon day number gives that weekday's name.
            'days' => collect(self::DAYS)->mapWithKeys(fn (int $d) => [$d => Carbon::parse('2024-01-07')->addDays($d)->translatedFormat('l')]),
            'blockedPeriods' => BlockedPeriod::with('doctor')->where('ends_at', '>=', now()->startOfDay())->orderBy('starts_at')->get(),
            'visitTypes' => VisitType::orderBy('sort')->orderBy('id')->get(),
        ]);
    }
}
