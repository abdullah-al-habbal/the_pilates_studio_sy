<?php

declare(strict_types=1);

namespace App\Actions\V1\BookingSession;

use App\Handlers\V1\BookingSession\ListUserSessionsHandler;
use App\Http\Requests\Api\V1\BookingSession\ListUserSessionsRequest;
use App\Http\Resources\Api\V1\BookingSessionCollection;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Booking Sessions')]
final readonly class ListUserSessionsAction
{
    use ApiResponseTrait;

    public function __construct(
        private ListUserSessionsHandler $handler,
    ) {}

    #[Endpoint('List user sessions (upcoming/past)', description: 'Returns paginated list of user booking sessions split by upcoming (future) or past (history).')]
    public function __invoke(ListUserSessionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->getType();
        $perPage = $request->getPerPage();

        $sessions = $this->handler->handle($user->id, $type, $perPage);

        return $this->success(new BookingSessionCollection($sessions));
    }
}
