<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetClientsHandler;
use App\Http\Requests\Admin\Operations\GetClientsRequest;
use App\Http\Resources\Admin\Operations\ClientListItemResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class GetClientsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetClientsHandler $handler
    ) {}

    /**
     * Search and list clients with unified pagination.
     */
    public function __invoke(GetClientsRequest $request): JsonResponse
    {
        $paginator = $this->handler->handle(
            $request->query('search'),
            (int) $request->query('page', 1)
        );

        return $this->paginated(
            $paginator,
            ClientListItemResource::class,
            message: 'Clients retrieved successfully.'
        );
    }
}
