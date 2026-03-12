<?php

namespace App\Filament\Admin\Resources\AppNotifications\Pages;

use App\Filament\Admin\Resources\AppNotifications\AppNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAppNotification extends EditRecord
{
    protected static string $resource = AppNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
