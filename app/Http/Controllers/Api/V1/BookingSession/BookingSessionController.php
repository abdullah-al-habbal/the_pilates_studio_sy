<?php

// filePath: app/Http/Controllers/Api/V1/BookingSession/BookingSessionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\BookingSession;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\BookingSession\ReserveSessionRequest;
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

    #[Endpoint('Reserve class session', description: 'Books a class session using booking credits. Requires active booking with credits.')]
    public function reserve(ReserveSessionRequest $request): JsonResponse
    {
        $activeBooking = $request->attributes->get('active_booking');

        $bookingSession = $this->bookingSessionService->reserve(
            bookingId: (int) $activeBooking->id,
            classSessionId: (int) $request->class_session_id
        );

        return $this->created(new BookingSessionResource($bookingSession));
    }

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

    #[Endpoint('Cancel booking session', description: 'Cancels a reserved booking session and refunds credit if within policy.')]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $this->bookingSessionService->cancel($id);

        return $this->success([
            'id' => $id,
            'status' => 'cancelled',
            'is_cancelled' => true,
        ]);
    }
}
