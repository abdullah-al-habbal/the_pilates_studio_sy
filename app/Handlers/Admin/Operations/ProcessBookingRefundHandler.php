<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Refund;
use App\Services\Validation\RefundValidatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ProcessBookingRefundHandler
{
    public function __construct(
        private RefundValidatorService $refundValidator
    ) {
    }

    public function handle(int $bookingId, ?int $amount): Refund
    {
        return DB::transaction(function () use ($bookingId, $amount): Refund {
            $booking = Booking::lockForUpdate()->findOrFail($bookingId);

            $this->refundValidator->assertRefundEligible($booking, $amount);

            $refundAmount = $amount ?? $booking->paid_amount;

            $snapshot = $booking->exchange_rate_snapshot;
            $exchangeRateSnapshot = $snapshot !== null && $snapshot > 0
                ? $snapshot
                : ($booking->currency?->exchange_rate ?? 1.0);

            $refund = Refund::create([
                'refundable_type' => Booking::class,
                'refundable_id' => $booking->id,
                'user_id' => $booking->user_id,
                'currency_id' => $booking->currency_id,
                'amount' => $refundAmount,
                'exchange_rate_snapshot' => $exchangeRateSnapshot,
                'refunded_by' => Auth::id(),
                'refunded_at' => now(),
            ]);

            $booking->update([
                'status' => BookingStatusEnum::CANCELLED,
                'remaining_credits' => 0,
            ]);

            return $refund;
        });
    }
}
