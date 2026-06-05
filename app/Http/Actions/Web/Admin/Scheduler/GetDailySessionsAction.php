<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Handlers\Admin\Scheduler\GetDailySessionsHandler;
use App\Http\Requests\Admin\Scheduler\GetDailySessionsRequest;
use App\Http\Resources\Admin\Scheduler\DailySessionResource;
use App\Queries\Admin\Scheduler\GetDailySessionsQuery;
use App\Services\Log\LoggingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class GetDailySessionsAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetDailySessionsHandler $handler,
        private readonly LoggingService $logger
    ) {
    }

    public function __invoke(GetDailySessionsRequest $request): JsonResponse
    {
        try {
            $instructorId = $request->getInstructorId();

            $this->logger->info('[Scheduler:GetDailySessions] Fetching sessions', [
                'date' => $request->getDate(),
                'instructor_id' => $instructorId,
            ]);

            $query = new GetDailySessionsQuery(
                date: $request->getDate(),
                perPage: $request->getPerPage(),
                page: (int) $request->input('page', 1),
                instructorId: $instructorId,
            );

            $paginator = $this->handler->handle($query);

            $this->logger->info('[Scheduler:GetDailySessions] Sessions fetched', [
                'count' => $paginator->count(),
                'date' => $request->getDate(),
            ]);

            return $this->success(
                data: DailySessionResource::collection($paginator->items()),
                meta: [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            );

        } catch (Throwable $e) {
            $this->logger->error('[Scheduler:GetDailySessions] Failed', [
                'error' => $e->getMessage(),
                'date' => $request->getDate(),
            ]);
            report($e);
            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to retrieve daily sessions.'
            );
        }
    }
}
