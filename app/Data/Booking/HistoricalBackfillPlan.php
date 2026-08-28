<?php

declare(strict_types=1);

namespace App\Data\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * A validated, fully resolved historical backfill, ready to write.
 *
 * Produced by HistoricalBackfillValidatorService so the service layer never recomputes a derived
 * value — in particular the terminal status, which decides whether the booking will occupy
 * `active_user_id` and therefore whether the D-A01 conflict guard had to run.
 */
final readonly class HistoricalBackfillPlan
{
    /**
     * @param  list<int>  $attendedSessionIds
     * @param  list<int>  $missedSessionIds
     */
    public function __construct(
        public User $user,
        public Package $package,
        public CarbonInterface $purchasedAt,
        public CarbonInterface $expiresAt,
        public int $currencyId,
        public int $paidAmount,
        public float $exchangeRateSnapshot,
        public bool $exchangeRateWasOverridden,
        public float $currentExchangeRate,
        public array $attendedSessionIds,
        public array $missedSessionIds,
        public int $remainingCredits,
        public BookingStatusEnum $terminalStatus,
    ) {}

    public function totalCredits(): int
    {
        return $this->package->total_credits;
    }

    public function attendedCount(): int
    {
        return count($this->attendedSessionIds);
    }

    public function missedCount(): int
    {
        return count($this->missedSessionIds);
    }

    /**
     * Every selected session, attended first. Sorted so the write path locks class_sessions in a
     * deterministic order across concurrent backfills.
     *
     * @return list<int>
     */
    public function allSessionIds(): array
    {
        $ids = [...$this->attendedSessionIds, ...$this->missedSessionIds];
        sort($ids);

        return array_values($ids);
    }
}
