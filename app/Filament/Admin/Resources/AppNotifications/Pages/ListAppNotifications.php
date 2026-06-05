<?php

namespace App\Filament\Admin\Resources\AppNotifications\Pages;

use App\Filament\Admin\Resources\AppNotifications\AppNotificationResource;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListAppNotifications extends ListRecords
{
    use Translatable;

    protected static string $resource = AppNotificationResource::class;
}
