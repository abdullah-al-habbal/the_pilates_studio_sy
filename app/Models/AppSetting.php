<?php

// filePath: app/Models/AppSetting.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppSettings\AppSettingTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'key'   => 'string',
            'value' => 'string',
            'type'  => AppSettingTypeEnum::class,
        ];
    }
}
