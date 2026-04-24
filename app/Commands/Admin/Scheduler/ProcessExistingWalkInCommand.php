<?php
declare(strict_types=1);

namespace App\Commands\Admin\Scheduler;

final readonly class ProcessExistingWalkInCommand
{
    /**
     * @param int[] $userIds
     */
    public function __construct(
        public int $sessionId,
        public array $userIds,
    ) {
    }
}
