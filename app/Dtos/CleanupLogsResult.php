<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class CleanupLogsResult
{
    public function __construct(
        public int $deletedDays,
        public array $messages,
    ) {}
}
