<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;

final readonly class GetSessionsDaysInMonthHandler
{
    public function __construct(
        private ClassSessionEloquentRepository $repository
    ) {}

    /**
     * @return string[]
     */
    public function handle(int $year, int $month): array
    {
        return $this->repository->getScheduledDatesInMonth($year, $month);
    }
}
