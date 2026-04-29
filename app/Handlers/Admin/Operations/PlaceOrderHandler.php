<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\MerchandiseOrder;
use App\Services\Merchandise\MerchandiseOrderService;

final readonly class PlaceOrderHandler
{
    public function __construct(
        private MerchandiseOrderService $orderService
    ) {}

    /**
     * Delegate order placement to the service.
     */
    public function handle(int $customerId, int $merchandiseId, int $quantity): MerchandiseOrder
    {
        return $this->orderService->placeOrder($customerId, $merchandiseId, $quantity);
    }
}
