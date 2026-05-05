<?php

declare(strict_types=1);

namespace App\Services\Merchandise;

use App\Models\CenterMerchandise;
use App\Models\MerchandiseOrder;
use App\Repositories\Eloquent\CenterMerchandise\CenterMerchandiseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Services\Log\LoggingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseOrderService
{
    public function __construct(
        private readonly CenterMerchandiseEloquentRepository $merchandiseRepo,
        private readonly MerchandiseOrderEloquentRepository $orderRepo,
        private readonly LoggingService $logger
    ) {
    }

    public function placeOrder(int $customerId, int $merchandiseId, int $quantity, int $currencyId): MerchandiseOrder
    {
        return DB::transaction(function () use ($customerId, $merchandiseId, $quantity, $currencyId): MerchandiseOrder {
            /** @var CenterMerchandise $item */
            $item = CenterMerchandise::lockForUpdate()->findOrFail($merchandiseId);

            if ($item->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock. Available: {$item->stock_quantity}.",
                ]);
            }

            $price = $item->getPriceForCurrency($currencyId);
            if (!$price) {
                throw ValidationException::withMessages([
                    'currency_id' => 'No price defined for this currency.',
                ]);
            }

            $item->decrement('stock_quantity', $quantity);

            return MerchandiseOrder::create([
                'merchandise_id' => $merchandiseId,
                'customer_id' => $customerId,
                'quantity' => $quantity,
                'ordered_at' => now(),
                'currency_id' => $currencyId,
                'paid_amount' => $price * $quantity,
            ]);
        });
    }

    public function deleteOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findOrFail($orderId);

            $this->merchandiseRepo->incrementStock($order->merchandise_id, $order->quantity);

            $this->orderRepo->delete($orderId);
        });
    }
}