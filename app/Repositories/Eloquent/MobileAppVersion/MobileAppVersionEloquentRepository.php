<?php
// filePath: app/Repositories/Eloquent/MobileAppVersion/MobileAppVersionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Models\MobileAppVersion\MobileAppVersion;

class MobileAppVersionEloquentRepository
{
    public function __construct(
        private MobileAppVersion $model
    ) {}

    public function findActiveByAppAndPlatform(
        AppNameEnum $appName,
        MobilePlatformEnum $platform
    ): ?MobileAppVersion {
        return $this->model->newQuery()
            ->where('app_name', $appName)
            ->where('platform', $platform)
            ->where('active', true)
            ->first();
    }
}
