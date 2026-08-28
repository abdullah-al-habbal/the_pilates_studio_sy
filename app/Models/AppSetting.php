<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppSettings\AppSettingTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string|null $description
 * @property AppSettingTypeEnum $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value', 'description', 'type'])]
class AppSetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'key' => 'string',
            'value' => 'string',
            'type' => AppSettingTypeEnum::class,
        ];
    }
}
