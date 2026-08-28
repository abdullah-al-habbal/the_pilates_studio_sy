<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Data\Booking\HistoricalBackfillPlan;
use App\Enums\BookingStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Services\Currency\PricingService;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Validates a historical backfill and resolves it into a writable plan.
 *
 * Rule order is load-bearing; see validate().
 *
 * @see docs/historical-backfill/decisions/D-A01-active-booking-conflict.md
 * @see docs/historical-backfill/decisions/D-A02-null-validity-packages.md
 * @see docs/historical-backfill/decisions/D-A03-exchange-rate-override.md
 */
final readonly class HistoricalBackfillValidatorService
{
    private const LANG = 'dashboard.operations_ui.historical_backfill.';

    public function __construct(
        private AssignPackageValidatorService $assignPackageValidator,
        private PricingService $pricingService,
        private BookingEloquentRepository $bookingRepository,
    ) {}

    /**
     * @param  list<int>  $attendedSessionIds
     * @param  list<int>  $missedSessionIds
     */
    public function validate(
        User $user,
        int $packageId,
        CarbonInterface $purchasedAt,
        ?int $currencyId,
        ?int $paidAmount,
        array $attendedSessionIds,
        array $missedSessionIds,
        ?float $exchangeRateOverride = null,
    ): HistoricalBackfillPlan {
        $package = Package::find($packageId);

        if ($package === null) {
            throw ValidationException::withMessages([
                'package_id' => __(self::LANG . 'error_package_not_found'),
            ]);
        }

        // 1. Validity gate (D-A02) — FIRST, before price and before the terminal-status
        //    computation, which calls addDays($package->validity_days) and would fatal on null.
        $this->assertPackageHasValidity($package);

        $this->assertPurchaseDateIsPast($purchasedAt);

        $expiresAt = $purchasedAt->copy()->addDays($package->validity_days);

        // 2. Price — reuses the standard assign-package rules verbatim.
        $currencyId ??= $this->pricingService->getBaseCurrencyId();
        $resolvedAmount = $this->assignPackageValidator
            ->validateAndComputeAmount($package->id, $currencyId, $paidAmount);

        // 3. Everything else.
        $attendedSessionIds = $this->normaliseIds($attendedSessionIds);
        $missedSessionIds = $this->normaliseIds($missedSessionIds);

        $this->assertNoOverlap($attendedSessionIds, $missedSessionIds);

        $attended = count($attendedSessionIds);
        $missed = count($missedSessionIds);

        $this->assertCreditsNotExceeded($package->total_credits, $attended, $missed);
        $this->assertSessionsExistWithinWindow(
            [...$attendedSessionIds, ...$missedSessionIds],
            $purchasedAt,
            $expiresAt,
        );

        $remainingCredits = $package->total_credits - $attended - $missed;
        $terminalStatus = $this->resolveTerminalStatus($remainingCredits, $expiresAt);

        // 4. Conflict guard (D-A01) — only when the result would occupy active_user_id.
        if ($terminalStatus === BookingStatusEnum::ACTIVE) {
            $this->assertNoBlockingActiveBooking($user);
        }

        $currentRate = $this->pricingService->getExchangeRateForSnapshot($currencyId);

        return new HistoricalBackfillPlan(
            user: $user,
            package: $package,
            purchasedAt: $purchasedAt,
            expiresAt: $expiresAt,
            currencyId: $currencyId,
            paidAmount: $resolvedAmount,
            exchangeRateSnapshot: $exchangeRateOverride ?? $currentRate,
            exchangeRateWasOverridden: $exchangeRateOverride !== null,
            currentExchangeRate: $currentRate,
            attendedSessionIds: $attendedSessionIds,
            missedSessionIds: $missedSessionIds,
            remainingCredits: $remainingCredits,
            terminalStatus: $terminalStatus,
        );
    }

    /**
     * A package with no defined validity can never expire, so a backfill leaving credits on it
     * would be permanently ACTIVE and permanently occupy active_user_id.
     *
     * Tests `> 0`, not `=== null`: validity_days = 0 is reachable (CreatePackageRequest allows
     * min:0), is displayed to admins as "Unlimited" (PackageInfolist), and behaves identically to
     * null everywhere — including in the observer that derives expires_at.
     */
    private function assertPackageHasValidity(Package $package): void
    {
        if (! ($package->validity_days > 0)) {
            throw ValidationException::withMessages([
                'package_id' => __(self::LANG . 'error_null_validity_package'),
            ]);
        }
    }

    private function assertPurchaseDateIsPast(CarbonInterface $purchasedAt): void
    {
        if ($purchasedAt->isFuture()) {
            throw ValidationException::withMessages([
                'purchased_at' => __(self::LANG . 'error_purchase_date_in_future'),
            ]);
        }
    }

    /**
     * @param  list<int>  $attended
     * @param  list<int>  $missed
     */
    private function assertNoOverlap(array $attended, array $missed): void
    {
        $overlap = array_intersect($attended, $missed);

        if ($overlap !== []) {
            throw ValidationException::withMessages([
                'missed_session_ids' => __(self::LANG . 'error_session_overlap'),
            ]);
        }
    }

    private function assertCreditsNotExceeded(int $totalCredits, int $attended, int $missed): void
    {
        if ($attended + $missed > $totalCredits) {
            throw ValidationException::withMessages([
                'attended_session_ids' => __(self::LANG . 'error_credit_overflow', [
                    'total' => $totalCredits,
                    'selected' => $attended + $missed,
                ]),
            ]);
        }
    }

    /**
     * @param  list<int>  $sessionIds
     */
    private function assertSessionsExistWithinWindow(
        array $sessionIds,
        CarbonInterface $purchasedAt,
        CarbonInterface $expiresAt,
    ): void {
        if ($sessionIds === []) {
            return;
        }

        $found = ClassSession::query()
            ->whereIn('id', $sessionIds)
            ->where('status', '!=', ClassSessionStatusEnum::CANCELLED->value)
            ->whereBetween('date', [$purchasedAt->toDateString(), $expiresAt->toDateString()])
            ->pluck('id')
            ->all();

        $missing = array_diff($sessionIds, $found);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'attended_session_ids' => __(self::LANG . 'error_session_out_of_range'),
            ]);
        }
    }

    /**
     * Exhausted wins over expired when both apply: it is the more informative label, and either
     * way the generated column resolves to NULL so there is no conflict to guard against.
     */
    private function resolveTerminalStatus(int $remainingCredits, CarbonInterface $expiresAt): BookingStatusEnum
    {
        return match (true) {
            $remainingCredits <= 0 => BookingStatusEnum::EXHAUSTED,
            $expiresAt->isPast() => BookingStatusEnum::EXPIRED,
            default => BookingStatusEnum::ACTIVE,
        };
    }

    /**
     * Uses the unfiltered predicate that matches the generated column, NOT
     * BookingService::assertNoActiveBooking(), which filters on expiry and so misses stale rows.
     */
    private function assertNoBlockingActiveBooking(User $user): void
    {
        $conflict = $this->bookingRepository->findBlockingActiveBooking($user->id);

        if ($conflict === null) {
            return;
        }

        throw ValidationException::withMessages([
            'package_id' => __(self::LANG . 'error_active_booking_conflict', [
                'client_name' => $user->fullname,
                'package_name' => $conflict->package?->name ?? '—',
                'remaining_credits' => $conflict->remaining_credits,
            ]),
        ]);
    }

    /**
     * @param  list<int>  $ids
     *
     * @return list<int>
     */
    private function normaliseIds(array $ids): array
    {
        return array_values(array_unique(array_map(intval(...), $ids)));
    }
}
