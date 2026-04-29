<?php
declare(strict_types=1);
namespace App\Handlers\BookingSession;
use App\Enums\Api\ErrorCodeEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use Illuminate\Validation\ValidationException;
class ReserveSessionHandler
{
    public function __invoke(Booking $booking, ClassSession $classSession): BookingSession
    {
        // Check expiry before reserving
        if ($booking->isExpired()) {
            throw ValidationException::withMessages([
                'booking' => ErrorCodeEnum::BOOKING_EXPIRED->getMessage(),
            ])->errorCode(ErrorCodeEnum::BOOKING_EXPIRED->value);
        }
        if ($booking->remaining_credits <= 0) {
            throw ValidationException::withMessages([
                'booking' => 'No credits remaining',
            ]);
        }
        // Existing reservation logic...
        $booking->deductCredit();
        return BookingSession::create([
            'booking_id' => $booking->id,
            'class_session_id' => $classSession->id,
            'status' => BookingSessionStatusEnum::RESERVED,
        ]);
    }
}
