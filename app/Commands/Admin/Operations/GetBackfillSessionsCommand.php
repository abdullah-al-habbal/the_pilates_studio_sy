<?php

declare(strict_types=1);

namespace App\Commands\Admin\Operations;

use Carbon\CarbonInterface;

final readonly class GetBackfillSessionsCommand
{
    public function __construct(
        public CarbonInterface $purchasedAt,
        public CarbonInterface $expiresAt,
        public int $perPage,
        public ?string $cursor = null,
        public ?int $month = null,
        public ?int $year = null,
        public ?int $excludeUserId = null,
    ) {}
}
