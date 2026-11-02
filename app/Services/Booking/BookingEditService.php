<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Refund;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Services\Log\LoggingService;
use App\Support\Time\StudioTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Direct edit of an assigned package. Only the dates are editable: money, the exchange-rate
 * snapshot, the credit total and the package itself stay as they were recorded.
 */
final readonly class BookingEditService
{
    public function __construct(
        private BookingService $bookings,
        private BookingEloquentRepository $repository,
        private LoggingService $logger,
    ) {}

    public function updateDates(
        int $bookingId,
        CarbonInterface $purchasedAt,
        ?CarbonInterface $expiresAt,
        int $adminId,
    ): Booking {
        return DB::transaction(function () use ($bookingId, $purchasedAt, $expiresAt, $adminId): Booking {
            $booking = $this->bookings->find($bookingId, true);

            $this->assertEditable($booking);
            $this->assertDatesAreSane($purchasedAt, $expiresAt);

            $before = [
                'purchased_at' => $booking->purchased_at?->toDateString(),
                'expires_at' => $booking->expires_at?->toDateString(),
                'status' => $booking->status->value,
            ];

            $booking->forceFill([
                'purchased_at' => $purchasedAt->startOfDay(),
                'expires_at' => $expiresAt?->endOfDay(),
            ])->save();

            $this->reconcileStatus($booking);

            $this->logger->info('Booking dates edited by admin', [
                'booking_id' => $booking->id,
                'admin_id' => $adminId,
                'before' => $before,
                'after' => [
                    'purchased_at' => $booking->purchased_at?->toDateString(),
                    'expires_at' => $booking->expires_at?->toDateString(),
                    'status' => $booking->status->value,
                ],
            ]);

            return $booking;
        });
    }

    private function assertEditable(Booking $booking): void
    {
        if (in_array($booking->status, [BookingStatusEnum::CANCELLED, BookingStatusEnum::FROZEN], true)
            || Refund::query()->where('refundable_type', Booking::class)->where('refundable_id', $booking->id)->exists()) {
            throw ValidationException::withMessages([
                'booking_id' => 'A cancelled, frozen or refunded package cannot be edited.',
            ]);
        }
    }

    private function assertDatesAreSane(CarbonInterface $purchasedAt, ?CarbonInterface $expiresAt): void
    {
        if ($purchasedAt->startOfDay()->gt(StudioTime::now()->endOfDay())) {
            throw ValidationException::withMessages([
                'purchased_at' => 'The purchase date cannot be in the future.',
            ]);
        }

        if ($expiresAt !== null && $expiresAt->startOfDay()->lt($purchasedAt->startOfDay())) {
            throw ValidationException::withMessages([
                'expires_at' => 'The expiry date cannot be before the purchase date.',
            ]);
        }
    }

    /**
     * No credits left → EXHAUSTED; past expiry → EXPIRED; otherwise ACTIVE, which also has to
     * be allowed by the one-active-package-per-client rule.
     */
    private function reconcileStatus(Booking $booking): void
    {
        $target = match (true) {
            $booking->remaining_credits <= 0 => BookingStatusEnum::EXHAUSTED,
            $booking->expires_at !== null && $booking->expires_at->isPast() => BookingStatusEnum::EXPIRED,
            default => BookingStatusEnum::ACTIVE,
        };

        if ($target === $booking->status) {
            return;
        }

        if ($target === BookingStatusEnum::ACTIVE) {
            $blocking = $this->repository->findBlockingActiveBooking($booking->user_id, lock: true);

            if ($blocking !== null && $blocking->id !== $booking->id) {
                throw ValidationException::withMessages([
                    'expires_at' => 'These dates would reactivate this package, but the client already has another active package.',
                ]);
            }
        }

        $this->repository->updateStatus($booking->id, $target);
        $booking->refresh();
    }
}
