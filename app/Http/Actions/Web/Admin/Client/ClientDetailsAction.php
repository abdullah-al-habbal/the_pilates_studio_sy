<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Client;

use App\Handlers\Admin\Operations\GetClientDetailsHandler;
use App\Http\Requests\Admin\Operations\GetClientDetailsRequest;
use App\Http\Resources\Admin\Operations\ClientDetailsResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class ClientDetailsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetClientDetailsHandler $handler
    ) {}

    /**
     * Get comprehensive details for a specific client.
     */
    public function __invoke(GetClientDetailsRequest $request, int $userId): JsonResponse
    {
        $user = $this->handler->handle($userId);

        return $this->success(
            data: new ClientDetailsResource($user),
            message: 'Client details retrieved successfully.'
        );
    }
}
