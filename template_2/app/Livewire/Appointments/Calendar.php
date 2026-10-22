<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use App\Models\User;
use App\Services\Scheduling\CalendarData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Appointments')]
class Calendar extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public string $view = 'day';

    /** Selected doctor (week view, or to narrow the day view). 0 = all doctors. */
    #[Url(as: 'doctor')]
    public int $doctorId = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Appointment::class);

        $this->date = $this->parsedDate()->toDateString();

        if (! in_array($this->view, ['day', 'week'], true)) {
            $this->view = 'day';
        }

        // Doctors without access to other schedules always see their own.
        if (! auth()->user()->can('appointments.view_all')) {
            $this->doctorId = auth()->id();
        }
    }

    protected function parsedDate(): CarbonImmutable
    {
        try {
            return $this->date ? CarbonImmutable::parse($this->date)->startOfDay() : CarbonImmutable::today();
        } catch (\Throwable) {
            return CarbonImmutable::today();
        }
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function doctors(): Collection
    {
        $user = auth()->user();

        return User::query()->doctors()->active()->with('doctorProfile')
            ->when(! $user->can('appointments.view_all'), fn ($q) => $q->whereKey($user->id))
            ->orderBy('name')
            ->get();
    }

    public function previous(): void
    {
        $this->date = $this->parsedDate()->sub($this->view === 'week' ? '1 week' : '1 day')->toDateString();
    }

    public function next(): void
    {
        $this->date = $this->parsedDate()->add($this->view === 'week' ? '1 week' : '1 day')->toDateString();
    }

    public function today(): void
    {
        $this->date = today()->toDateString();
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'week' ? 'week' : 'day';

        if ($this->view === 'week' && ! $this->doctorId) {
            $this->doctorId = (int) $this->doctors->first()?->id;
        }
    }

    public function selectDoctor(int $id): void
    {
        if (auth()->user()->can('appointments.view_all') || $id === auth()->id()) {
            $this->doctorId = $id;
        }
    }

    #[On('appointment-saved')]
    #[On('appointment-updated')]
    public function refresh(): void
    {
        unset($this->doctors);
    }

    /** Saturday-based week, as is usual in Egypt. */
    protected function weekStart(): CarbonImmutable
    {
        return $this->parsedDate()->startOfWeek(CarbonImmutable::SATURDAY);
    }

    public function render(CalendarData $calendar)
    {
        $user = auth()->user();
        $date = $this->parsedDate();
        $doctors = $this->doctors;

        if ($this->view === 'week') {
            $doctor = $doctors->firstWhere('id', $this->doctorId) ?? $doctors->first();
            $grid = $doctor ? $calendar->week($this->weekStart(), $doctor, $user) : ['start' => 540, 'end' => 1020, 'columns' => []];
            $rangeLabel = $this->weekStart()->translatedFormat('j M').' – '.$this->weekStart()->addDays(6)->translatedFormat('j M Y');
        } else {
            $shown = $this->doctorId ? $doctors->where('id', $this->doctorId)->values() : $doctors;
            $grid = $calendar->day($date, $shown, $user);
            $rangeLabel = $date->translatedFormat('l j F Y');
        }

        return view('livewire.appointments.calendar', [
            'grid' => $grid,
            'rangeLabel' => $rangeLabel,
            'isToday' => $date->isToday(),
            'canBook' => $user->can('create', Appointment::class),
        ]);
    }
}
