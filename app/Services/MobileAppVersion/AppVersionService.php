<?php
// filePath: app/Services/MobileAppVersion/AppVersionService.php

declare(strict_types=1);

namespace App\Services\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Models\MobileAppVersion\MobileAppVersion;
use App\Repositories\Eloquent\MobileAppVersion\MobileAppVersionEloquentRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

readonly class AppVersionService
{
    public function __construct(
        private MobileAppVersionEloquentRepository $repository
    ) {
    }

    public function getCompatibility(
        AppNameEnum $appName,
        MobilePlatformEnum $platform,
        string $clientVersion,
    ): array {
        $config = $this->getActiveConfig($appName, $platform);

        if ($config === null) {
            Log::error('Missing mobile app version configuration', [
                'app_name' => $appName->value,
                'platform' => $platform->value,
                'error' => 'No active configuration found for this app and platform'
            ]);

            throw new RuntimeException(
                'Mobile app version configuration is missing. Please contact support.',
                503
            );
        }

        $updateRequired = $this->isBelowVersion($clientVersion, $config->min_version);

        $updateAvailable = !$updateRequired && $this->isBelowVersion($clientVersion, $config->latest_version);

        return [
            'update_required' => $updateRequired,
            'update_available' => $updateAvailable,
            'message' => $config->force_message,
            'store_url' => $config->store_url,
            'min_version' => $config->min_version,
            'latest_version' => $config->latest_version,
        ];
    }

    private function getActiveConfig(
        AppNameEnum $appName,
        MobilePlatformEnum $platform,
    ): ?MobileAppVersion {
        $cacheKey = "mobile_version:{$appName->value}:{$platform->value}";

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn() => $this->repository->findActiveByAppAndPlatform($appName, $platform)
        );
    }

    public function validateConfiguration(): void
    {
        $isValid = $this->repository->validateConfiguration();

        if (!$isValid) {
            throw new RuntimeException(
                'Invalid mobile app version configuration detected. Please check logs for details.',
                500
            );
        }
    }
    
    private function isBelowVersion(string $clientVersion, string $targetVersion): bool
    {
        return $this->compareSemver($clientVersion, $targetVersion) < 0;
    }

    private function compareSemver(string $a, string $b): int
    {
        $pa = explode('.', $a);
        $pb = explode('.', $b);

        if (count($pa) !== 3 || count($pb) !== 3) {
            throw new InvalidArgumentException('Invalid semantic version.');
        }

        foreach ([0, 1, 2] as $i) {
            $da = (int) $pa[$i];
            $db = (int) $pb[$i];

            if ($da < $db) {
                return -1;
            }

            if ($da > $db) {
                return 1;
            }
        }

        return 0;
    }
}
