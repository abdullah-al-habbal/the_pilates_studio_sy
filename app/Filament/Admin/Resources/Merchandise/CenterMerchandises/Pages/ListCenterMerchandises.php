<?php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\CenterMerchandiseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCenterMerchandises extends ListRecords
{
    protected static string $resource = CenterMerchandiseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
