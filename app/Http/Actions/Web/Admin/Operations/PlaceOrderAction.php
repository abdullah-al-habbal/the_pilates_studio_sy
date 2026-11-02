<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\DTOs\Operations\PlaceOrderDTO;
use App\Handlers\Admin\Operations\PlaceOrderHandler;
use App\Http\Requests\Admin\Operations\PlaceOrderRequest;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class PlaceOrderAction
{
    use ApiResponseTrait;

    public function __construct(
        private PlaceOrderHandler $handler,
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
                orderedAt: $request->filled('ordered_at') ? Carbon::parse((string) $request->input('ordered_at')) : null,
            );

            $order = $this->handler->handle($dto);

            return $this->created(
                data: $order,
                message: __('dashboard.operations_ui.store.sale_success'),
            );
        } catch (ValidationException $e) {
            // Domain rule (e.g. insufficient stock): let Laravel render field errors as 422.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Operations - PlaceOrder failed: ' . $e->getMessage(), [
                'exception' => $e,
                'customer_id' => $request->customer_id,
                'merchandise_id' => $request->merchandise_id,
            ]);

            return $this->unprocessable($e->getMessage());
        }
    }
}
