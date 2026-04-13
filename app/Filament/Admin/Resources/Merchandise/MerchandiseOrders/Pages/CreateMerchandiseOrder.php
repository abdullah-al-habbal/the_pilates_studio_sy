<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\MerchandiseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMerchandiseOrder extends CreateRecord
{
    protected static string $resource = MerchandiseOrderResource::class;
}
