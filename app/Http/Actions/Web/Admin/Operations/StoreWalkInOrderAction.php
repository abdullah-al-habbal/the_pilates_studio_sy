<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\StoreWalkInOrderHandler;
use App\Http\Requests\Admin\Operations\StoreWalkInOrderRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class StoreWalkInOrderAction
{
    use ApiResponseTrait;

    public function __construct(
        private StoreWalkInOrderHandler $handler
    ) {
    }

    public function __invoke(StoreWalkInOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->handler->handle(
                // fix: use a command class to hande the attributes
                merchandiseId: (int) $request->merchandise_id,
                quantity: (int) $request->quantity,
                currencyId: (int) $request->currency_id,
                fullname: $request->fullname,
                phoneNumber: $request->phone_number,
                email: $request->email,
            );

            return $this->created(
                data: $order,
                message: 'Walk-in sale recorded successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - StoreWalkInOrder failed: ' . $e->getMessage(), [
                'exception' => $e,
                'merchandise_id' => $request->merchandise_id,
                'phone_number' => $request->phone_number,
            ]);

            return $this->unprocessable($e->getMessage());
        }
    }
}
