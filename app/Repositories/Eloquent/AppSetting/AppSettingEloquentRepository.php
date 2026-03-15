<?php
// filePath: app/Repositories/Eloquent/Setting/AppSettingEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Setting;

use App\Models\UserSetting;

class AppSettingEloquentRepository
{
    public function firstOrCreateForUser(int $userId): UserSetting
    {
        return UserSetting::firstOrCreate(
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
