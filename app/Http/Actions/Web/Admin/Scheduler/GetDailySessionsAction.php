<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\GetDailySessionsHandler;
use App\Http\Requests\Admin\Scheduler\GetDailySessionsRequest;
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
            $locale = app()->getLocale();

            $items = collect($paginator->items())->map(function (object $session) use ($locale): array {
                $title = match (true) {
                    is_array($session->class?->title) => $session->class->title[$locale]
                    ?? $session->class->title['en']
                    ?? '[MISSING:class.title.translation]',
                    !is_null($session->class?->title) => $session->class->title,
                    is_null($session->class) => '[MISSING:class_session.class_relation]',
                    default => '[MISSING:class.title]',
                };

                $instructor = $session->class?->instructor?->fullname
                    ?? ($session->class ? '[MISSING:instructor.fullname]' : '[MISSING:class_relation→instructor]');

                $reserved = $session->bookingSessions->count();
                $capacity = (int) ($session->total_spots ?? 0);
                $attended = $session->bookingSessions
                    ->filter(fn($bs) => $bs->attendance_status?->value === 'attended')
                    ->count();

                return [
                    'id' => $session->id,
                    'title' => $title,
                    'instructor' => $instructor,
                    'start_time' => substr($session->start_time ?? '00:00', 0, 5),
                    'end_time' => substr($session->end_time ?? '00:00', 0, 5),
                    'duration_minutes' => $session->duration_minutes,
                    'capacity' => $capacity,
                    'reserved' => $reserved,
                    'attended' => $attended,
                    'available_spots' => $capacity > 0 ? max(0, $capacity - $reserved) : null,
                    'is_full' => $capacity > 0 && $reserved >= $capacity,
                    'fill_pct' => $capacity > 0
                        ? min(100, (int) round($reserved / $capacity * 100))
                        : 0,
                ];
            });

            $meta = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ];

            $this->logger->info('[Scheduler:GetDailySessions] Sessions fetched', [
                'count' => count($items),
                'date' => $request->getDate(),
            ]);

            return $this->success(data: $items, code: SuccessCodeEnum::SUCCESS, meta: $meta);

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
