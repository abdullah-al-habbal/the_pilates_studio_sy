<?php
declare(strict_types=1);
namespace App\Handlers\BookingSession;
use App\Enums\BookingSessionStatusEnum;
use App\Models\BookingSession;
use App\Models\User;
class CancelSessionHandler
{
    public function __invoke(
        BookingSession $bookingSession,
        ?string $reason = null,
        ?User $cancelledBy = null
    ): void {
        $bookingSession->update([
            'status' => BookingSessionStatusEnum::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by_user_id' => $cancelledBy?->id,
        ]);
        // Refund credit to booking
        $bookingSession->booking->refundCredit();
    }
}
