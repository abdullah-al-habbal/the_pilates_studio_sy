<?php
declare(strict_types=1);

namespace App\Queries\Admin\Scheduler;

final readonly class GetDailySessionsQuery
{
    public function __construct(
        public string $date,
        public int    $perPage = 10,
        public int    $page = 1,
    ) {}
}
