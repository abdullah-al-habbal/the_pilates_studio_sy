<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Http\Requests\Admin\Operations\UpdateBookingRequest;
use App\Services\Booking\BookingEditService;
use App\Traits\ApiResponseTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class UpdateBookingAction
{
    use ApiResponseTrait;

    public function __construct(
        private BookingEditService $editor,
    ) {}

    public function __invoke(UpdateBookingRequest $request, int $bookingId): JsonResponse
    {
        try {
            $expires = $request->validated('expires_at');

            $this->editor->updateDates(
                bookingId: $bookingId,
                purchasedAt: CarbonImmutable::parse($request->validated('purchased_at')),
                expiresAt: $expires === null ? null : CarbonImmutable::parse($expires),
                adminId: (int) $request->user()->id,
            );

            return $this->success(message: 'Package updated successfully.');
        } catch (ValidationException $e) {
            return $this->unprocessable(
                collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                $e->errors(),
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Booking not found.');
        } catch (\Throwable $e) {
            Log::error('Operations - UpdateBooking failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
                'actor_id' => $request->user()?->id,
            ]);

            return $this->error(message: 'Failed to update package.', status: 500);
        }
    }
}
