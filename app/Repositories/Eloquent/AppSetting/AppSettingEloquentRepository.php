<?php
// filePath: app/Repositories/Eloquent/AppSetting/AppSettingEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\AppSetting;

use App\Models\AppSetting;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppSettingEloquentRepository
{
    public function findByKey(string $key): ?AppSetting
    {
        return AppSetting::where('key', $key)->first();
    }

    public function findByKeyOrFail(string $key): AppSetting
    {
        $setting = $this->findByKey($key);

        if (!$setting) {
            throw (new ModelNotFoundException)->setModel(
                AppSetting::class,
                $key
            );
        }

        return $setting;
    }

    public function exists(string $key): bool
    {
        return AppSetting::where('key', $key)->exists();
    }
}
