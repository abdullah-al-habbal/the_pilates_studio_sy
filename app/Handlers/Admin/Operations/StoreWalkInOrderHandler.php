<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\MerchandiseOrder;
use App\Services\BookingSession\BookingSessionService;
use App\Services\Merchandise\MerchandiseOrderService;
use Illuminate\Support\Facades\DB;

final readonly class StoreWalkInOrderHandler
{
    public function __construct(
        private BookingSessionService $bookingSessionService,
        private MerchandiseOrderService $orderService,
    ) {
    }

    public function handle(
        int $merchandiseId,
        int $quantity,
        int $currencyId,
        string $fullname,
        string $phoneNumber,
        ?string $email,
        ?int $createdBy = null,
    ): MerchandiseOrder {
        return DB::transaction(function () use ($merchandiseId, $quantity, $currencyId, $fullname, $phoneNumber, $email, $createdBy): MerchandiseOrder {
            $user = $this->bookingSessionService->createWalkInUser([
                'fullname' => $fullname,
                'phone_number' => $phoneNumber,
                'email' => $email,
                'password' => 'pilates',
            ]);

            return $this->orderService->placeOrder($user->id, $merchandiseId, $quantity, $currencyId, $createdBy);
        });
    }
}
