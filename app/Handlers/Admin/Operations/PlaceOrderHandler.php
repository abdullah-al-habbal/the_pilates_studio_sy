<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\DTOs\Operations\PlaceOrderDTO;
use App\Models\MerchandiseOrder;
use App\Services\Merchandise\MerchandiseOrderService;

final readonly class PlaceOrderHandler
{
    public function __construct(
        private MerchandiseOrderService $orderService
    ) {}

    public function handle(PlaceOrderDTO $dto): MerchandiseOrder
    {
        return $this->orderService->placeOrder($dto);
    }
}
