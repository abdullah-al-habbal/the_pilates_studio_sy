<?php

declare(strict_types=1);

namespace App\Support\Operations;

use App\Models\Booking;

final class BookingPackageMapper
{
    public static function toArray(?Booking $booking): ?array
    {
        if ($booking === null) {
            return null;
        }

        return [
            'id' => $booking->id,
            'name' => $booking->package?->getTranslation('name', app()->getLocale()),
            'total_credits' => $booking->total_credits,
            'remaining_credits' => $booking->remaining_credits,
            'status' => $booking->status->value ?? (string) $booking->status,
            'expires_at' => $booking->expires_at?->format('M d, Y'),
            'source_type' => $booking->source_type?->value ?? $booking->source_type,
            'remaining_days' => $booking->expires_at
                ? (int) now()->diffInDays($booking->expires_at, false)
                : null,
            'paid_amount' => $booking->paid_amount,
            'currency_id' => $booking->currency_id,
        ];
    }
}
