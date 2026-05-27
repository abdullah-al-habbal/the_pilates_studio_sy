<?php

namespace App\Filament\Admin\Resources\AppSettings\Pages;

use App\Filament\Admin\Resources\AppSettings\AppSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditAppSetting extends EditRecord
{
    protected static string $resource = AppSettingResource::class;
}
