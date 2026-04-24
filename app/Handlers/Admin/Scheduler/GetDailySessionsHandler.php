<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Models\ClassSession;
use App\Queries\Admin\Scheduler\GetDailySessionsQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetDailySessionsHandler
{
    public function handle(GetDailySessionsQuery $query): LengthAwarePaginator
    {
        return ClassSession::with([
            'class.instructor',
            'bookingSessions',
        ])
            ->whereDate('date', $query->date)
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
