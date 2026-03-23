<?php
// filePath: app/Repositories/Eloquent/UserSetting/UserSettingEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\UserSetting;

use App\Models\UserSetting;

class UserSettingEloquentRepository
{
    public function __construct(
        private UserSetting $model
    ) {}

    public function firstOrCreateForUser(int $userId): UserSetting
    {
        return $this->model->firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        )->load('preferredLanguage');
    }

    public function updateAndLoad(UserSetting $settings, array $data): UserSetting
    {
        $settings->update($data);

        return $settings->fresh()->load('preferredLanguage');
    }
}
