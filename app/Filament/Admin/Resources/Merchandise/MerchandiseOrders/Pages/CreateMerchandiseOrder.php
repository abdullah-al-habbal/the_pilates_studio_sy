<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\MerchandiseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMerchandiseOrder extends CreateRecord
{
    protected static string $resource = MerchandiseOrderResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(\App\Services\Merchandise\MerchandiseOrderService::class)->placeOrder(
            customerId: (int) $data['customer_id'],
            merchandiseId: (int) $data['merchandise_id'],
            quantity: (int) $data['quantity']
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
