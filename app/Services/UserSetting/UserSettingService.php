<?php
// filePath: app/Services/UserSetting/UserSettingService.php

declare(strict_types=1);

namespace App\Services\UserSetting;

use App\Models\UserSetting;
use App\Repositories\Eloquent\UserSetting\UserSettingEloquentRepository;

class UserSettingService
{
    public function __construct(
        private readonly UserSettingEloquentRepository $repository
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

    public function updateFcmToken(int $userId, string $token): UserSetting
    {
        $settings = $this->repository->firstOrCreateForUser($userId);
        return $this->repository->updateAndLoad($settings, ['fcm_token' => $token]);
    }
}
