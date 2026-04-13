<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\MerchandiseOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMerchandiseOrder extends EditRecord
{
    protected static string $resource = MerchandiseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
