<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\DTOs\Operations\PlaceOrderDTO;
use App\Handlers\Admin\Operations\PlaceOrderHandler;
use App\Http\Requests\Admin\Operations\PlaceOrderRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class PlaceOrderAction
{
    use ApiResponseTrait;

    public function __construct(
        private PlaceOrderHandler $handler
    ) {}

    public function __invoke(PlaceOrderRequest $request): JsonResponse
    {
        try {
            $dto = new PlaceOrderDTO(
                customerId: (int) $request->customer_id,
                merchandiseId: (int) $request->merchandise_id,
                quantity: (int) $request->quantity,
                currencyId: (int) $request->currency_id,
                createdBy: $request->user()?->id,
            );

            $order = $this->handler->handle($dto);

            return $this->created(
                data: $order,
                message: 'Order placed successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - PlaceOrder failed: '.$e->getMessage(), [
                'exception' => $e,
                'customer_id' => $request->customer_id,
                'merchandise_id' => $request->merchandise_id,
            ]);

            return $this->unprocessable($e->getMessage());
        }
    }
}
