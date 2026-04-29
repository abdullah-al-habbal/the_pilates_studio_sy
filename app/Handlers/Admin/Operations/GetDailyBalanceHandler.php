<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Services\Finance\DailyBalanceService;
use Carbon\CarbonInterface;

final readonly class GetDailyBalanceHandler
{
    public function __construct(
        private DailyBalanceService $balanceService
    ) {}

    /**
     * Fetch daily financial metrics.
     */
    public function handle(CarbonInterface $date): array
    {
        return $this->balanceService->compute($date);
    }
}
