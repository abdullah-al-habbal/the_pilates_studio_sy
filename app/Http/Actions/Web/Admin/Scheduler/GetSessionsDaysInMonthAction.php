<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetSessionsDaysInMonthAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ClassSessionEloquentRepository $repository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $dates = $this->repository->getScheduledDatesInMonth($year, $month);

        return $this->success($dates, message: 'Session dates retrieved.');
    }
}
