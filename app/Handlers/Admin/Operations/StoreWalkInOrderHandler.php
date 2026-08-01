<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\DTOs\Operations\PlaceOrderDTO;
use App\DTOs\Operations\WalkInOrderDTO;
use App\Models\MerchandiseOrder;
use App\Services\BookingSession\BookingSessionService;
use App\Services\Merchandise\MerchandiseOrderService;
use Illuminate\Support\Facades\DB;

final readonly class StoreWalkInOrderHandler
{
    public function __construct(
        private BookingSessionService $bookingSessionService,
        private MerchandiseOrderService $orderService,
    ) {}

    public function handle(WalkInOrderDTO $dto): MerchandiseOrder
    {
        return DB::transaction(function () use ($dto): MerchandiseOrder {
            $user = $this->bookingSessionService->createWalkInUser([
                'fullname' => $dto->fullname,
                'phone_number' => $dto->phoneNumber,
                'email' => $dto->email,
                'password' => 'pilates',
            ]);

            return $this->orderService->placeOrder(new PlaceOrderDTO(
                customerId: $user->id,
                merchandiseId: $dto->merchandiseId,
                quantity: $dto->quantity,
                currencyId: $dto->currencyId,
                createdBy: $dto->createdBy,
            ));
        });
    }
}
