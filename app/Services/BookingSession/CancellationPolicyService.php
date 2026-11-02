<?php

declare(strict_types=1);

namespace App\Services\BookingSession;

use App\Models\ClassSession;
use App\Support\Time\StudioTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * The only place that decides whether a reservation may still be cancelled.
 *
 * Rules (all evaluated in the studio timezone):
 *  - once the class has started, nobody may cancel, admins included;
 *  - inside the 24h window before the start, only an admin override may cancel.
 */
final class CancellationPolicyService
{
    public const WINDOW_HOURS = 24;

    public function startsAt(ClassSession $session): ?CarbonImmutable
    {
        return $session->startsAt();
    }

    public function cutoff(ClassSession $session): ?CarbonImmutable
    {
        return $session->startsAt()?->subHours(self::WINDOW_HOURS);
    }

    public function hasStarted(ClassSession $session, ?CarbonInterface $now = null): bool
    {
        $startsAt = $session->startsAt();

        // An unreadable start time must never open a cancellation path.
        return $startsAt === null || ($now ?? StudioTime::now())->greaterThanOrEqualTo($startsAt);
    }

    public function isInsideWindow(ClassSession $session, ?CarbonInterface $now = null): bool
    {
        $cutoff = $this->cutoff($session);

        return $cutoff === null || ($now ?? StudioTime::now())->greaterThanOrEqualTo($cutoff);
    }

    public function canCancel(ClassSession $session, bool $isAdminOverride = false, ?CarbonInterface $now = null): bool
    {
        if ($this->hasStarted($session, $now)) {
            return false;
        }

        return $isAdminOverride || ! $this->isInsideWindow($session, $now);
    }

    /**
     * @throws ValidationException
     */
    public function assertCancellable(ClassSession $session, bool $isAdminOverride = false, ?CarbonInterface $now = null): void
    {
        if ($this->hasStarted($session, $now)) {
            throw ValidationException::withMessages([
                'cancelled_at' => 'This class has already started and can no longer be cancelled.',
            ]);
        }

        if (! $isAdminOverride && $this->isInsideWindow($session, $now)) {
            throw ValidationException::withMessages([
                'cancelled_at' => 'Cancellation window has passed (24h before session).',
            ]);
        }
    }
}
