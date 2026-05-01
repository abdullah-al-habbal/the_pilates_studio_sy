<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetStoreItemsHandler;
use App\Http\Requests\Admin\Operations\GetStoreItemsRequest;
use App\Http\Resources\Admin\Operations\MerchandiseResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class GetStoreItemsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetStoreItemsHandler $handler
    ) {
    }

    public function __invoke(GetStoreItemsRequest $request): JsonResponse
    {
        try {
            $items = $this->handler->handle();

            return $this->success(
                data: MerchandiseResource::collection($items),
                message: 'Store items retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetStoreItems failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->error(message: 'Failed to retrieve store items.');
        }
    }
}
