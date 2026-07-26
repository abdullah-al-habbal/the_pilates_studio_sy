<?php

declare(strict_types=1);

namespace App\Commands;

final readonly class ApplyCapacityToFutureSessionsCommand
{
    public function __construct(
        public int $classId,
        public string $reason,
    ) {}
}
