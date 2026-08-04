<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetClientsHandler;
use App\Http\Requests\Admin\Operations\GetClientsRequest;
use App\Http\Resources\Admin\Operations\ClientListItemResource;
use App\Http\Resources\Admin\Operations\ClientOptionResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class GetClientsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetClientsHandler $handler
    ) {}

    public function __invoke(GetClientsRequest $request): JsonResponse
    {
        try {
            $paginator = $this->handler->handle(
                $request->toCommand()
            );

            Log::info('Operations - GetClients', [
                'pagination' => $request->query('pagination', 'offset'),
                'search' => $request->query('search'),
                'cursor' => $request->query('cursor'),
                'only_clients' => (bool) $request->query('only_clients', false),
                'with_valid_fcm' => (bool) $request->query('with_valid_fcm', false),
                'result_class' => get_class($paginator),
                'count' => $paginator->count(),
                'next_cursor' => $paginator instanceof CursorPaginatorContract
                    ? $paginator->nextCursor()?->encode()
                    : null,
                'has_more' => $paginator->hasMorePages(),
            ]);

            if ($paginator instanceof CursorPaginatorContract) {
                return $this->success(
                    data: ClientOptionResource::collection($paginator->items()),
                    message: 'Clients retrieved successfully.',
                    meta: [
                        'next_cursor' => $paginator->nextCursor()?->encode(),
                        'has_more' => $paginator->hasMorePages(),
                    ]
                );
            }

            return $this->paginated(
                $paginator,
                ClientListItemResource::class,
                message: 'Clients retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetClients failed: '.$e->getMessage(), [
                'exception' => $e,
                'search' => $request->query('search'),
            ]);

            return $this->error(message: 'Failed to retrieve clients.');
        }
    }
}
