<?php
// filePath: app/Enums/MobileAppVersion/MobilePlatformEnum.php

declare(strict_types=1);

namespace App\Enums\MobileAppVersion;

enum MobilePlatformEnum: string
{
    case ANDROID = 'android';
    case IOS     = 'ios';
}
