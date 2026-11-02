<?php

declare(strict_types=1);

namespace App\Support\Time;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Single source of truth for "studio wall-clock" time.
 *
 * Class sessions store `date` and `start_time`/`end_time` as local studio time, while the
 * application timezone stays UTC for timestamps and finance data.
 */
final class StudioTime
{
    public static function timezone(): string
    {
        return (string) config('app.studio_timezone', 'Asia/Damascus');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    /**
     * Combine a date and a `H:i[:s]` time into an instant in the studio timezone.
     */
    /**
     * Last instant of the studio day on which a package of $days days stops being valid,
     * expressed in the application timezone so it is stored consistently.
     */
    public static function validUntil(int $days): Carbon
    {
        return Carbon::instance(self::now()->addDays($days)->endOfDay())->setTimezone(config('app.timezone'));
    }

    public static function at(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date . ' ' . $time, self::timezone());
    }
}
