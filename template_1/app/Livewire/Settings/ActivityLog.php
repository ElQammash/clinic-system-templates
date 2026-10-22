<?php

namespace App\Livewire\Settings;

use App\Enums\Permission;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Who did what: medical record access, edits, deleted invoices, logins, settings.
 */
#[Title('Activity log')]
class ActivityLog extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $log = '';

    #[Url(as: 'user', except: 0)]
    public int $userId = 0;

    #[Url(except: '')]
    public string $event = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public ?int $detailsId = null;

    public function mount(): void
    {
        $this->authorize(Permission::ActivityLogView->value);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    /** Human label for "log_name / event". */
    public static function describe(Activity $activity): string
    {
        $key = 'activity.'.$activity->event;

        return trans()->has($key) ? __($key) : ($activity->description ?: (string) $activity->event);
    }

    public function render()
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->log, fn ($q) => $q->where('log_name', $this->log))
            ->when($this->userId, fn ($q) => $q->where('causer_type', User::class)->where('causer_id', $this->userId))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest('id')
            ->paginate(30);

        return view('livewire.settings.activity-log', [
            'activities' => $activities,
            'logs' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values(),
            'events' => Activity::query()->distinct()->orderBy('event')->pluck('event')->filter()->values(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'details' => $this->detailsId ? Activity::find($this->detailsId) : null,
        ]);
    }
}
