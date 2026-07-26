<?php

declare(strict_types=1);

namespace App\Commands\Admin\Scheduler;

final readonly class UpdateSessionCapacityCommand
{
    public function __construct(
        public int $sessionId,
        public int $capacity,
        public string $reason,
    ) {}
}
