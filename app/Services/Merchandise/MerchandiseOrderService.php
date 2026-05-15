<?php

declare(strict_types=1);

namespace App\Services\Merchandise;

use App\Models\CenterMerchandise;
use App\Models\MerchandiseOrder;
use App\Repositories\Eloquent\CenterMerchandise\CenterMerchandiseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Services\Currency\PricingService;
use App\Services\Log\LoggingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseOrderService
{
    public function __construct(
        private readonly CenterMerchandiseEloquentRepository $merchandiseRepo,
        private readonly MerchandiseOrderEloquentRepository $orderRepo,
        private readonly LoggingService $logger,
        private readonly PricingService $pricingService,
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

            $basePrice = $this->pricingService->getBasePrice($item);

            if ($basePrice === null) {
                throw ValidationException::withMessages([
                    'merchandise_id' => 'No base price defined for this item.',
                ]);
            }

            $paidAmount = $this->pricingService->calculateAmount($basePrice * $quantity, $currencyId);

            $item->decrement('stock_quantity', $quantity);

            $exchangeRateSnapshot = $this->pricingService->getExchangeRateForSnapshot($currencyId);

            return MerchandiseOrder::create([
                'merchandise_id' => $merchandiseId,
                'customer_id' => $customerId,
                'quantity' => $quantity,
                'ordered_at' => now(),
                'currency_id' => $currencyId,
                'paid_amount' => $paidAmount,
                'exchange_rate_snapshot' => $exchangeRateSnapshot,
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