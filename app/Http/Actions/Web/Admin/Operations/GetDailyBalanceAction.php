<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Services\Finance\DailyBalanceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class GetDailyBalanceAction
{
    use ApiResponseTrait;

    public function __construct(
        private DailyBalanceService $balanceService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // fix: make a FormRequest and a Command class.
        try {
            $date = $request->query('date', now()->toDateString());
            $currencies = $request->query('currencies', []);
            $convertToBase = $request->boolean('convertToBase', false);

            $summary = $this->balanceService->getSummary(
                date: $date,
                currencies: is_array($currencies) ? $currencies : null,
                convertToBase: $convertToBase
            );

            return $this->success(
                data: $summary->values()->all(),
                message: 'Daily balance retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetDailyBalance failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->error(message: 'Failed to retrieve daily balance.');
        }
    }
}
