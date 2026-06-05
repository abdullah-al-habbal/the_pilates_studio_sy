<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Commands\Admin\Operations\GetDailyBalanceCommand;
use App\Data\Reports\CurrencySummaryData;
use App\Http\Requests\Admin\Operations\GetDailyBalanceRequest;
use App\Services\Finance\DailyBalanceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class GetDailyBalanceAction
{
    use ApiResponseTrait;

    public function __construct(
        private DailyBalanceService $balanceService
    ) {}

    public function __invoke(GetDailyBalanceRequest $request): JsonResponse
    {
        try {
            $command = new GetDailyBalanceCommand(
                date: $request->getDate(),
                currencyCodes: $request->currencyCodes(),
                convertToBase: $request->convertToBase(),
            );

            $summary = $this->balanceService->getSummary(
                date: $command->date,
                currencies: $command->currencyCodes,
                convertToBase: $command->convertToBase,
            );

            return $this->success(
                data: $summary->map(
                    fn(CurrencySummaryData $item): array => $item->toArray()
                )->values()->all(),
                message: 'Daily balance retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetDailyBalance failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->error(message: 'Failed to retrieve daily balance.');
        }
    }
}
