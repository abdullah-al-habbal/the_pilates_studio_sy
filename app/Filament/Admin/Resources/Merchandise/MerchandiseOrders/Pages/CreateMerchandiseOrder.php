<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\MerchandiseOrderResource;
use App\Models\Currency;
use App\Services\Merchandise\MerchandiseOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMerchandiseOrder extends CreateRecord
{
    protected static string $resource = MerchandiseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $currencyId = (int) ($data['currency_id'] ?? Currency::where('is_active', true)->value('id'));
        if (!$currencyId) {
            throw new \RuntimeException('No active currency found.');
        }

        return app(MerchandiseOrderService::class)->placeOrder(
            customerId: (int) $data['customer_id'],
            merchandiseId: (int) $data['merchandise_id'],
            quantity: (int) $data['quantity'],
            currencyId: $currencyId,
            createdBy: (int) auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
