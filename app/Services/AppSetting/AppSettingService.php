<?php
// filePath: app/Services/Setting/AppSettingService.php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Models\UserSetting;
use App\Repositories\Eloquent\Setting\AppSettingEloquentRepository;

class AppSettingService
{
    public function __construct(
        private readonly AppSettingEloquentRepository $repository
    ) {}

    public function getUserSettings(int $userId): UserSetting
    {
        return $this->repository->firstOrCreateForUser($userId);
    }

    public function updateUserSettings(int $userId, array $data): UserSetting
    {
        $settings = $this->repository->firstOrCreateForUser($userId);

        return $this->repository->updateAndLoad($settings, $data);
    }
}
