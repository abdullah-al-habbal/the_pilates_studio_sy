<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\UnfreezeBookingHandler;
use App\Support\Booking\ActiveBookingConstraint;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class UnfreezeBookingAction
{
    use ApiResponseTrait;

    public function __construct(
        private UnfreezeBookingHandler $handler,
    ) {}

    public function __invoke(int $bookingId): JsonResponse
    {
        try {
            $newBooking = $this->handler->handle($bookingId);

            return $this->success(
                data: $newBooking,
                message: 'Booking unfrozen successfully. A new booking has been created for the remaining validity.',
            );
        } catch (ValidationException $e) {
            // Field-keyed so the modal can attach it; the active-booking rejection names the
            // blocking package. Folding it into unprocessable() would drop the errors bag.
            throw $e;
        } catch (QueryException $e) {
            Log::error('Operations - UnfreezeBooking hit a database error: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
            ]);

            // Last resort. The guard in BookingFreezeService should make this unreachable, but a
            // race between that check and the insert must not hand the admin a raw SQL string.
            if (ActiveBookingConstraint::isViolatedBy($e)) {
                return $this->unprocessable(__('dashboard.operations_ui.errors.active_booking_generic'));
            }

            return $this->unprocessable(__('dashboard.operations_ui.errors.unfreeze_failed'));
        } catch (\Throwable $e) {
            Log::error('Operations - UnfreezeBooking failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
            ]);

            return $this->unprocessable($e->getMessage());
        }
    }
}
