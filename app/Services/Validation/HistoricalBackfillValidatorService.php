<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Commands\Admin\Operations\HistoricalBackfillCommand;
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

final readonly class HistoricalBackfillValidatorService
{
    private const LANG = 'dashboard.operations_ui.historical_backfill.';

    public function __construct(
        private AssignPackageValidatorService $assignPackageValidator,
        private PricingService $pricingService,
        private BookingEloquentRepository $bookingRepository,
    ) {}

    public function validate(
        User $user,
        HistoricalBackfillCommand $command,
    ): HistoricalBackfillPlan {
        $purchasedAt = $command->purchasedAt;
        $package = Package::find($command->packageId);

        if ($package === null) {
            throw ValidationException::withMessages([
                'package_id' => __(self::LANG . 'error_package_not_found'),
            ]);
        }

        $this->assertPackageHasValidity($package);

        $this->assertPurchaseDateIsPast($purchasedAt);

        $expiresAt = $purchasedAt->copy()->addDays($package->validity_days);

        $currencyId = $command->currencyId ?? $this->pricingService->getBaseCurrencyId();
        $resolvedAmount = $this->assignPackageValidator
            ->validateAndComputeAmount($package->id, $currencyId, $command->paidAmount);

        $attendedSessionIds = $this->normaliseIds($command->attendedSessionIds);
        $missedSessionIds = $this->normaliseIds($command->missedSessionIds);

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
            exchangeRateSnapshot: $command->exchangeRateOverride ?? $currentRate,
            exchangeRateWasOverridden: $command->exchangeRateOverride !== null,
            currentExchangeRate: $currentRate,
            attendedSessionIds: $attendedSessionIds,
            missedSessionIds: $missedSessionIds,
            remainingCredits: $remainingCredits,
            terminalStatus: $terminalStatus,
        );
    }

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

    private function resolveTerminalStatus(int $remainingCredits, CarbonInterface $expiresAt): BookingStatusEnum
    {
        return match (true) {
            $remainingCredits <= 0 => BookingStatusEnum::EXHAUSTED,
            $expiresAt->isPast() => BookingStatusEnum::EXPIRED,
            default => BookingStatusEnum::ACTIVE,
        };
    }

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

    private function normaliseIds(array $ids): array
    {
        return array_values(array_unique(array_map(intval(...), $ids)));
    }
}
