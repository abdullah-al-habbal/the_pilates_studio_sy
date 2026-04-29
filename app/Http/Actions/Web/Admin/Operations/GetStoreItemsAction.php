<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetStoreItemsHandler;
use App\Http\Requests\Admin\Operations\GetStoreItemsRequest;
use App\Http\Resources\Admin\Operations\MerchandiseResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class GetStoreItemsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetStoreItemsHandler $handler
    ) {}

    /**
     * Fetch store items with validated request.
     */
    public function __invoke(GetStoreItemsRequest $request): JsonResponse
    {
        $items = $this->handler->handle();

        return $this->success(
            data: MerchandiseResource::collection($items),
            message: 'Store items retrieved successfully.'
        );
    }
}
