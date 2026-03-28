<?php
// filePath: app/Models/MobileAppVersion/MobileAppVersion.php

declare(strict_types=1);

namespace App\Models\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use Illuminate\Database\Eloquent\Model;

class MobileAppVersion extends Model
{
    protected $table = 'mobile_app_versions';

    protected $fillable = [
        'app_name',
        'platform',
        'min_version',
        'latest_version',
        'force_message',
        'store_url',
        'active',
    ];

    protected $casts = [
        'app_name' => AppNameEnum::class,
        'platform' => MobilePlatformEnum::class,
        'active' => 'bool',
    ];
}
