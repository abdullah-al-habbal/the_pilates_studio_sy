<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Commands\BookingSession\CancelBookingSessionCommand;
use App\Handlers\BookingSession\CancelBookingSessionHandler;
use App\Http\Requests\Admin\Operations\CancelBookingSessionRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class CancelBookingSessionAction
{
    use ApiResponseTrait;

    public function __construct(
        private CancelBookingSessionHandler $handler,
    ) {}

    public function __invoke(CancelBookingSessionRequest $request, int $bookingSessionId): JsonResponse
    {
        try {
            $this->handler->handle(new CancelBookingSessionCommand(
                bookingSessionId: $bookingSessionId,
                actorId: (int) $request->user()->id,
                isAdminOverride: $request->boolean('override'),
                reason: $request->validated('reason'),
            ));

            return $this->success(message: 'Booking session cancelled successfully.');
        } catch (ValidationException $e) {
            return $this->unprocessable(
                collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                $e->errors(),
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Booking session not found.');
        } catch (\Throwable $e) {
            Log::error('Operations - CancelBookingSession failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_session_id' => $bookingSessionId,
                'actor_id' => $request->user()?->id,
            ]);

            return $this->error(message: 'Failed to cancel booking session.', status: 500);
        }
    }
}
