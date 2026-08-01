<?php

declare(strict_types=1);

namespace App\Services\Merchandise;

use App\DTOs\Operations\PlaceOrderDTO;
use App\Models\CenterMerchandise;
use App\Models\MerchandiseOrder;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Services\Currency\PricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseOrderService
{
    public function __construct(
        private readonly MerchandiseOrderEloquentRepository $orderRepo,
        private readonly PricingService $pricingService,
    ) {}

    public function placeOrder(PlaceOrderDTO $dto): MerchandiseOrder
    {
        return DB::transaction(function () use ($dto): MerchandiseOrder {
            /** @var CenterMerchandise $item */
            $item = CenterMerchandise::lockForUpdate()->findOrFail($dto->merchandiseId);

            if ($item->stock_quantity < $dto->quantity) {
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

            $paidAmount = $this->pricingService->calculateAmount($basePrice * $dto->quantity, $dto->currencyId);

            $item->decrement('stock_quantity', $dto->quantity);

            $exchangeRateSnapshot = $this->pricingService->getExchangeRateForSnapshot($dto->currencyId);

            return MerchandiseOrder::create([
                'merchandise_id' => $dto->merchandiseId,
                'customer_id' => $dto->customerId,
                'created_by' => $dto->createdBy,
                'quantity' => $dto->quantity,
                'ordered_at' => now(),
                'currency_id' => $dto->currencyId,
                'paid_amount' => $paidAmount,
                'exchange_rate_snapshot' => $exchangeRateSnapshot,
            ]);
        });
    }

    public function deleteOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $this->orderRepo->findOrFail($orderId);
            $this->orderRepo->delete($orderId);
        });
    }
}
