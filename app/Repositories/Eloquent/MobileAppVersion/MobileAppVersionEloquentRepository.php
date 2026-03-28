<?php
// filePath: app/Repositories/Eloquent/MobileAppVersion/MobileAppVersionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Models\MobileAppVersion\MobileAppVersion;
use Illuminate\Support\Facades\Log;

class MobileAppVersionEloquentRepository
{
    public function __construct(
        private MobileAppVersion $model
    ) {
    }

    public function findActiveByAppAndPlatform(
        AppNameEnum $appName,
        MobilePlatformEnum $platform
    ): ?MobileAppVersion {
        $query = $this->model->newQuery()
            ->where('app_name', $appName)
            ->where('platform', $platform)
            ->where('active', true);

        $count = $query->count();

        if ($count > 1) {
            Log::warning('Multiple active mobile app versions found', [
                'app_name' => $appName->value,
                'platform' => $platform->value,
                'count' => $count,
                'warning' => 'There should be only one active version per app/platform'
            ]);
        }

        return $query->first();
    }

    /**
     * Get all active configurations for validation
     */
    public function getAllActiveConfigs(): array
    {
        return $this->model->newQuery()
            ->where('active', true)
            ->get()
            ->groupBy(function ($item) {
                return $item->app_name->value . '_' . $item->platform->value;
            })
            ->toArray();
    }

    /**
     * Check if there's exactly one active configuration per app/platform
     */
    public function validateConfiguration(): bool
    {
        $configs = $this->model->newQuery()
            ->where('active', true)
            ->get();

        $grouped = $configs->groupBy(function ($item) {
            return $item->app_name->value . '_' . $item->platform->value;
        });

        $isValid = true;

        foreach ($grouped as $key => $group) {
            if ($group->count() > 1) {
                Log::error('Invalid mobile app version configuration', [
                    'group' => $key,
                    'count' => $group->count(),
                    'error' => 'Multiple active versions found for the same app and platform'
                ]);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
