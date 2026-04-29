<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Client;

use App\Handlers\Admin\Operations\GetClientDetailsHandler;
use App\Http\Requests\Admin\Operations\GetClientDetailsRequest;
use App\Http\Resources\Admin\Operations\ClientDetailsResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class ClientDetailsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetClientDetailsHandler $handler
    ) {}

    public function __invoke(GetClientDetailsRequest $request, int $userId): JsonResponse
    {
        try {
            $user = $this->handler->handle($userId);

            return $this->success(
                data: new ClientDetailsResource($user),
                message: 'Client details retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - ClientDetails failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $userId,
            ]);

            return $this->error(message: 'Failed to retrieve client details.');
        }
    }
}
