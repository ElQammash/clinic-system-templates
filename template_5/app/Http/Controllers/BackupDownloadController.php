<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Services\Backups;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(string $name, Backups $backups): StreamedResponse
    {
        Gate::authorize(Permission::BackupsManage->value);
        abort_unless($backups->exists($name), 404);

        activity('system')->causedBy(request()->user())->event('backup_downloaded')->withProperties(['file' => $name])->log('backup downloaded');

        return Storage::disk($backups->disk())->download($backups->folder().'/'.$name);
    }
}
