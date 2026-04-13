<?php
declare(strict_types=1);
namespace App\Services\Merchandise;

use App\Models\CenterMerchandise;
use App\Models\MerchandiseOrder;
use App\Services\Log\LoggingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseOrderService
{
    public function __construct(
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
            /** @var CenterMerchandise $merchandise */
            $merchandise = CenterMerchandise::lockForUpdate()->findOrFail($merchandiseId);

            if ($merchandise->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock. Available: {$merchandise->stock_quantity}, requested: {$quantity}.",
                ]);
            }

            $merchandise->decrement('stock_quantity', $quantity);

            return MerchandiseOrder::create([
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
            $order = MerchandiseOrder::findOrFail($orderId);
            CenterMerchandise::lockForUpdate()->findOrFail($order->merchandise_id)
                ->increment('stock_quantity', $order->quantity);
            $order->delete();
        });
    }
}