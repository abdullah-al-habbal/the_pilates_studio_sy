<?php

declare(strict_types=1);

namespace App\Models\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property AppNameEnum $app_name
 * @property MobilePlatformEnum $platform
 * @property string $min_version
 * @property string $latest_version
 * @property string $force_message
 * @property string $store_url
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'mobile_app_versions')]
#[Fillable([
    'app_name',
    'platform',
    'min_version',
    'latest_version',
    'force_message',
    'store_url',
    'active',
])]
class MobileAppVersion extends Model
{
    protected function casts(): array
    {
        return [
            'app_name' => AppNameEnum::class,
            'platform' => MobilePlatformEnum::class,
            'active' => 'bool',
        ];
    }
}
