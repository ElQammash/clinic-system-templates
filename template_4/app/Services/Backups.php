<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Thin wrapper around spatie/laravel-backup for the Settings → Backups page.
 */
class Backups
{
    public function disk(): string
    {
        return config('backup.backup.destination.disks')[0] ?? 'local';
    }

    public function folder(): string
    {
        return (string) config('backup.backup.name');
    }

    /** @return Collection<int, array{path: string, name: string, size: int, date: Carbon}> newest first */
    public function list(): Collection
    {
        $disk = Storage::disk($this->disk());

        if (! $disk->exists($this->folder())) {
            return collect();
        }

        return collect($disk->files($this->folder()))
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'size' => $disk->size($path),
                'date' => now()->setTimestamp($disk->lastModified($path)),
            ])
            ->sortByDesc('date')
            ->values();
    }

    /** @return array{ok: bool, output: string} */
    public function run(bool $onlyDb = false): array
    {
        try {
            $code = Artisan::call('backup:run', array_filter(['--only-db' => $onlyDb, '--disable-notifications' => true]));

            return ['ok' => $code === 0, 'output' => trim(Artisan::output())];
        } catch (Throwable $e) {
            return ['ok' => false, 'output' => $e->getMessage()];
        }
    }

    public function exists(string $name): bool
    {
        return basename($name) === $name && Storage::disk($this->disk())->exists($this->folder().'/'.$name);
    }
}
