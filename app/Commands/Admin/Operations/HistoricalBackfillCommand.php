<?php

declare(strict_types=1);

namespace App\Commands\Admin\Operations;

use Carbon\CarbonInterface;

final readonly class HistoricalBackfillCommand
{
    public function __construct(
        public int $userId,
        public int $packageId,
        public CarbonInterface $purchasedAt,
        public ?int $currencyId,
        public ?int $paidAmount,
        public array $attendedSessionIds,
        public array $missedSessionIds,
        public ?float $exchangeRateOverride = null,
    ) {}
}
