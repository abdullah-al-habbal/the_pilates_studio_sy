<?php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\CenterMerchandiseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCenterMerchandise extends EditRecord
{
    protected static string $resource = CenterMerchandiseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
