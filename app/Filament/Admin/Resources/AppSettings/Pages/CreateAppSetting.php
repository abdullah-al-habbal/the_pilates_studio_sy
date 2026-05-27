<?php

namespace App\Filament\Admin\Resources\AppSettings\Pages;

use App\Filament\Admin\Resources\AppSettings\AppSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppSetting extends CreateRecord
{
    protected static string $resource = AppSettingResource::class;
}
