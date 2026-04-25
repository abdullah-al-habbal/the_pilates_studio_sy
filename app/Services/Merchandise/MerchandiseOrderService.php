<?php

declare(strict_types=1);

namespace App\Services\Merchandise;

use App\Models\MerchandiseOrder;
use App\Repositories\Eloquent\CenterMerchandise\CenterMerchandiseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Services\Log\LoggingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function createOrder(
        int $merchandiseId,
        int $quantity,
        ?int $customerId = null,
    ): MerchandiseOrder {
        $this->logger->info('Creating merchandise order', [
            'merchandise_id' => $merchandiseId,
            'quantity' => $quantity,
        ]);

        return DB::transaction(function () use ($merchandiseId, $quantity, $customerId) {
            $merchandise = $this->merchandiseRepo->findForUpdate($merchandiseId);

            if (!$merchandise) {
                throw new ModelNotFoundException("Merchandise with ID {$merchandiseId} not found.");
            }

            if ($merchandise->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock. Available: {$merchandise->stock_quantity}, requested: {$quantity}.",
                ]);
            }

            $this->merchandiseRepo->decrementStock($merchandiseId, $quantity);

            return $this->orderRepo->create([
                'merchandise_id' => $merchandiseId,
                'quantity' => $quantity,
                'customer_id' => $customerId,
                'ordered_at' => now(),
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