<?php

namespace App\Actions\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateClinicSettings
{
    /**
     * @param  array<string, mixed>  $values  already validated
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function handle(array $values, ?UploadedFile $logo = null, bool $removeLogo = false): array
    {
        $disk = Storage::disk(config('clinic.logo.disk'));
        $oldLogo = settings('logo_path');

        if ($logo !== null) {
            $values['logo_path'] = $logo->store(config('clinic.logo.directory'), config('clinic.logo.disk'));
        } elseif ($removeLogo) {
            $values['logo_path'] = null;
        }

        $changes = settings()->set($values);

        if (array_key_exists('logo_path', $changes) && filled($oldLogo)) {
            $disk->delete($oldLogo);
        }

        if ($changes !== []) {
            activity('settings')
                ->event('updated')
                ->withProperties(['changes' => $changes])
                ->log('settings updated');
        }

        return $changes;
    }
}
