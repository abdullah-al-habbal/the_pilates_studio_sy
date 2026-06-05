<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetClientsHandler;
use App\Http\Requests\Admin\Operations\GetClientsRequest;
use App\Http\Resources\Admin\Operations\ClientListItemResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class GetClientsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetClientsHandler $handler
    ) {
    }

    public function __invoke(GetClientsRequest $request): JsonResponse
    {
        try {
            $paginator = $this->handler->handle(
                $request->toCommand()
            );

            return $this->paginated(
                $paginator,
                ClientListItemResource::class,
                message: 'Clients retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetClients failed: ' . $e->getMessage(), [
                'exception' => $e,
                'search'    => $request->query('search'),
            ]);

            return $this->error(message: 'Failed to retrieve clients.');
        }
    }
}