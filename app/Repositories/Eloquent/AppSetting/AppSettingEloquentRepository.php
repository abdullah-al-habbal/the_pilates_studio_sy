<?php
// filePath: app/Repositories/Eloquent/AppSetting/AppSettingEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\AppSetting;

use App\Models\AppSetting;

class AppSettingEloquentRepository
{
    public function getByKey(string $key): ?AppSetting
    {
        return AppSetting::where('key', $key)->first();
    }

}
