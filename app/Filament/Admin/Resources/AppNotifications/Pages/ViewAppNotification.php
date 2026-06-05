<?php

namespace App\Filament\Admin\Resources\AppNotifications\Pages;

use App\Filament\Admin\Resources\AppNotifications\AppNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewAppNotification extends ViewRecord
{
    use Translatable;

    protected static string $resource = AppNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
