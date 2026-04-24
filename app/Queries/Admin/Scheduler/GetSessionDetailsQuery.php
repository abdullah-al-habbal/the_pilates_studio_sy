<?php
declare(strict_types=1);

namespace App\Queries\Admin\Scheduler;

final readonly class GetSessionDetailsQuery
{
    public function __construct(
        public int $sessionId,
    ) {}
}
