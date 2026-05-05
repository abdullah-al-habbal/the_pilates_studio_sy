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
        try {
            $currencies = $request->query('currencies');
            $summary = $this->balanceService->getSummary($request->query('date'), is_array($currencies) ? $currencies : null);

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
