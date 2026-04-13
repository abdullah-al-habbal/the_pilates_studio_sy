<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\MerchandiseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMerchandiseOrders extends ListRecords
{
    protected static string $resource = MerchandiseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
