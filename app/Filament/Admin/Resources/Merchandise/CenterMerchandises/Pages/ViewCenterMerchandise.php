<?php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\CenterMerchandiseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCenterMerchandise extends ViewRecord
{
    protected static string $resource = CenterMerchandiseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
