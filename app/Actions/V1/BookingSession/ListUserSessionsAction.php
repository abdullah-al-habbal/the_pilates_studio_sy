<?php

declare(strict_types=1);

namespace App\Actions\V1\BookingSession;

use App\Handlers\V1\BookingSession\ListUserSessionsHandler;
use App\Http\Requests\Api\V1\BookingSession\ListUserSessionsRequest;
use App\Http\Resources\Api\V1\BookingSessionCollection;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

#[Group('Booking Sessions')]
final readonly class ListUserSessionsAction
{
    use ApiResponseTrait;

    public function __construct(
        private ListUserSessionsHandler $handler,
    ) {}

    #[Endpoint('List user sessions (upcoming/past)', description: 'Returns paginated list of user booking sessions. For type=both, returns separate upcoming and past lists each with independent pagination.')]
    public function __invoke(ListUserSessionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->getType();
        $perPage = $request->getPerPage();

        $result = $this->handler->handle($user->id, $type, $perPage);

        if ($type === 'both' && is_array($result)) {
            $upcomingCollection = new BookingSessionCollection($result['upcoming']);
            $pastCollection = new BookingSessionCollection($result['past']);

            $data = [
                'upcoming' => [
                    'data' => $upcomingCollection->toArray($request)['data'] ?? [],
                    'meta' => $this->extractPaginationMeta($result['upcoming']),
                ],
                'past' => [
                    'data' => $pastCollection->toArray($request)['data'] ?? [],
                    'meta' => $this->extractPaginationMeta($result['past']),
                ],
            ];

            return $this->success(['data' => $data]);
        }

        return $this->success(new BookingSessionCollection($result));
    }

    private function extractPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
