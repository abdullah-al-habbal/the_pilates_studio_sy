<?php
// filePath: database/seeders/MobileAppVersionSeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Models\MobileAppVersion\MobileAppVersion;
use Illuminate\Database\Seeder;

class MobileAppVersionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure only one active configuration per app/platform
        MobileAppVersion::updateOrCreate(
            [
                'app_name' => AppNameEnum::CUSTOMER,
                'platform' => MobilePlatformEnum::ANDROID,
            ],
            [
                'min_version' => '1.0.0',
                'latest_version' => '1.0.0',
                'force_message' => 'Please update to the latest version',
                'store_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
                'active' => true,
            ]
        );

        MobileAppVersion::updateOrCreate(
            [
                'app_name' => AppNameEnum::CUSTOMER,
                'platform' => MobilePlatformEnum::IOS,
            ],
            [
                'min_version' => '1.0.0',
                'latest_version' => '1.0.0',
                'force_message' => 'Please update to the latest version',
                'store_url' => 'https://apps.apple.com/app/id123456789',
                'active' => true,
            ]
        );
    }
}
