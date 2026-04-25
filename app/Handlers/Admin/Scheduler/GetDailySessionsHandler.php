<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Queries\Admin\Scheduler\GetDailySessionsQuery;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetDailySessionsHandler
{
    public function __construct(
        private ClassSessionEloquentRepository $repository
    ) {}

    public function handle(GetDailySessionsQuery $query): LengthAwarePaginator
    {
        return $this->repository->paginateDailySessions(
            $query->date,
            $query->perPage,
            $query->page
        );
    }
}
