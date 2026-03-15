<?php
// filePath: app/Http/Controllers/Api/V1/BookingSession/BookingSessionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\BookingSession;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\BookingSessionCollection;
use App\Http\Resources\Api\V1\BookingSessionResource;
use App\Services\BookingSession\BookingSessionService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Booking Sessions')]
class BookingSessionController extends BaseApiController
{
    public function __construct(
        private readonly BookingSessionService $bookingSessionService
    ) {}

    #[Endpoint('List booking sessions', description: 'Returns a paginated list of user booking sessions.')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'per_page']);
        $sessions = $this->bookingSessionService->listUserSessions($request->user()->id, $filters);

        return $this->success(new BookingSessionCollection($sessions));
    }

    #[Endpoint('Get booking session by ID', description: 'Returns a booking session by its ID.')]
    public function show(Request $request, int $id): JsonResponse
    {
        $session = $this->bookingSessionService->findUserSession($request->user()->id, $id);
        return $this->success(new BookingSessionResource($session));
    }
}
