<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Http\Resources\Admin\Operations\BookingSessionBreakdownResource;
use App\Models\Booking;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class ListBookingSessionsAction
{
    use ApiResponseTrait;

    public function __invoke(Request $request, int $bookingId): JsonResponse
    {
        try {
            $booking = Booking::query()->find($bookingId);

            if ($booking === null) {
                return $this->notFound('Booking not found.');
            }

            $sessions = $booking->bookingSessions()
                ->with(['classSession.class.instructor', 'classSession.class.category'])
                ->join('class_sessions', 'class_sessions.id', '=', 'booking_sessions.class_session_id')
                ->orderBy('class_sessions.date')
                ->orderBy('class_sessions.start_time')
                ->select('booking_sessions.*')
                ->get();

            return $this->success(data: BookingSessionBreakdownResource::collection($sessions)->resolve());
        } catch (\Throwable $e) {
            Log::error('Operations - ListBookingSessions failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
                'actor_id' => $request->user()?->id,
            ]);

            return $this->error(message: 'Failed to load sessions.', status: 500);
        }
    }
}
