<?php
// filePath: app/Services/MobileAppVersion/AppVersionService.php

declare(strict_types=1);

namespace App\Services\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Models\MobileAppVersion\MobileAppVersion;
use App\Repositories\Eloquent\MobileAppVersion\MobileAppVersionEloquentRepository;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

readonly class AppVersionService
{
    public function __construct(
        private MobileAppVersionEloquentRepository $repository
    ) {}

    public function getCompatibility(
        AppNameEnum $appName,
        MobilePlatformEnum $platform,
        string $clientVersion,
    ): array {
        $config = $this->getActiveConfig($appName, $platform);

        if ($config === null) {
            return $this->emptyCompatibility();
        }

        $updateRequired  = $this->isBelowMinVersion($clientVersion, $config->min_version);
        $updateAvailable = !$updateRequired
            && $this->isBelowLatestVersion($clientVersion, $config->latest_version);

        return [
            'update_required'  => $updateRequired,
            'update_available' => $updateAvailable,
            'message'          => $config->force_message,
            'store_url'        => $config->store_url,
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
            fn () => $this->repository->findActiveByAppAndPlatform($appName, $platform)
        );
    }

    private function emptyCompatibility(): array
    {
        return [
            'update_required'  => false,
            'update_available' => false,
            'message'          => null,
            'store_url'        => null,
        ];
    }

    private function isBelowMinVersion(string $clientVersion, string $minVersion): bool
    {
        return $this->compareSemver($clientVersion, $minVersion) < 0;
    }

    private function isBelowLatestVersion(string $clientVersion, string $latestVersion): bool
    {
        return $this->compareSemver($clientVersion, $latestVersion) < 0;
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
