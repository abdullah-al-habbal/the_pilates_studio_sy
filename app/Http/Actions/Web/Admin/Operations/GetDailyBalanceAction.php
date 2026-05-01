<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetDailyBalanceHandler;
use App\Http\Requests\Admin\Operations\GetDailyBalanceRequest;
use App\Http\Resources\Admin\Operations\DailyBalanceResource;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class GetDailyBalanceAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetDailyBalanceHandler $handler
    ) {
    }

    public function __invoke(GetDailyBalanceRequest $request): JsonResponse
    {
        try {
            $date = $request->query('date') ? Carbon::parse($request->query('date')) : today();
            $balance = $this->handler->handle($date);

            return $this->success(
                data: new DailyBalanceResource($balance),
                message: 'Daily balance retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetDailyBalance failed: ' . $e->getMessage(), [
                'exception' => $e,
                'date' => $request->query('date'),
            ]);

            return $this->error(message: 'Failed to retrieve daily balance.');
        }
    }
}
