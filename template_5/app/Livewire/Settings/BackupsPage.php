<?php

namespace App\Livewire\Settings;

use App\Enums\Permission;
use App\Services\Backups;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Backups')]
class BackupsPage extends Component
{
    public ?string $output = null;

    public bool $ok = true;

    public function mount(): void
    {
        $this->authorize(Permission::BackupsManage->value);
    }

    public function runNow(Backups $backups): void
    {
        $this->authorize(Permission::BackupsManage->value);

        $result = $backups->run();
        $this->ok = $result['ok'];
        $this->output = $result['output'];

        activity('system')->causedBy(auth()->user())->event('backup')->withProperties(['ok' => $this->ok])->log('manual backup');

        $this->dispatch('toast', message: $this->ok ? __('Backup completed.') : __('Backup failed. See the details below.'), type: $this->ok ? 'success' : 'error');
    }

    public function render(Backups $backups)
    {
        return view('livewire.settings.backups', [
            'backups' => $backups->list(),
            'keepDays' => config('clinic.backups.keep_days'),
        ]);
    }
}
