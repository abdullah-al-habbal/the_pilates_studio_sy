<?php
// filePath: app/Services/AppSetting/AppSettingService.php

declare(strict_types=1);

namespace App\Services\AppSetting;

use App\Models\AppSetting;
use App\Repositories\Eloquent\AppSetting\AppSettingEloquentRepository;

class AppSettingService
{
    public function __construct(
        private readonly AppSettingEloquentRepository $appSettingRepository
    ) {}

    public function getByKey(string $key): AppSetting
    {
        return $this->appSettingRepository->findByKeyOrFail($key);
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->appSettingRepository->findByKey($key);

        return $setting ? $setting->value : $default;
    }
}
