<?php

declare(strict_types=1);

namespace App\Support\Booking;

use Illuminate\Database\QueryException;

/**
 * Recognises a violation of `unique_active_booking_per_user`.
 *
 * Every write path that can populate `active_user_id` guards itself first, so this is the last
 * resort — a race that slipped between a guard and its insert, or a path nobody has guarded yet.
 * Without it such a write surfaces the raw SQL string to whoever is on the other end.
 */
final class ActiveBookingConstraint
{
    private const DUPLICATE_KEY = 1062;

    private const INDEX = 'unique_active_booking_per_user';

    /**
     * Narrowed to driver code 1062 AND the index name. SQLSTATE 23000 on its own also covers
     * foreign-key violations, and reporting one of those as "this client has an active booking"
     * sends an admin chasing a problem they do not have.
     */
    public static function isViolatedBy(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === self::DUPLICATE_KEY
            && str_contains($e->getMessage(), self::INDEX);
    }
}
