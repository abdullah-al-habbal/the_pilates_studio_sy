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
            // fix: we must use a command class instead of passing the parameters directly to the handler
            $paginator = $this->handler->handle(
                $request->query('search'),
                (int) $request->query('page', 1),
                $request->query('filter'),
                (int) $request->query('per_page', 15)
            );

            return $this->paginated(
                $paginator,
                ClientListItemResource::class,
                message: 'Clients retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetClients failed: ' . $e->getMessage(), [
                'exception' => $e,
                'search' => $request->query('search'),
            ]);

            return $this->error(message: 'Failed to retrieve clients.');
        }
    }
}
