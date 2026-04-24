<?php
declare(strict_types=1);

namespace App\Commands\Admin\Scheduler;

final readonly class ProcessNewWalkInCommand
{
    public function __construct(
        public int $sessionId,
        public string $fullname,
        public string $phoneNumber,
        public ?string $email,
        public string $password,
    ) {
    }
}
