<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Commands\BookingSession\BatchUpdateBookingSessionsCommand;
use App\Handlers\BookingSession\BatchUpdateBookingSessionsHandler;
use App\Http\Requests\Admin\Operations\BatchUpdateBookingSessionsRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class BatchUpdateBookingSessionsAction
{
    use ApiResponseTrait;

    public function __construct(
        private BatchUpdateBookingSessionsHandler $handler,
    ) {}

    public function __invoke(BatchUpdateBookingSessionsRequest $request, int $bookingId): JsonResponse
    {
        try {
            $this->handler->handle(new BatchUpdateBookingSessionsCommand(
                bookingId: $bookingId,
                actorId: (int) $request->user()->id,
                items: $request->validated('items'),
            ));

            return $this->success(message: 'Sessions updated successfully.');
        } catch (ValidationException $e) {
            return $this->unprocessable(
                collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                $e->errors(),
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Booking session not found.');
        } catch (\Throwable $e) {
            Log::error('Operations - BatchUpdateBookingSessions failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
                'actor_id' => $request->user()?->id,
            ]);

            return $this->error(message: 'Failed to update sessions.', status: 500);
        }
    }
}
