<?php

declare(strict_types=1);

namespace App\Actions;

use App\Commands\ApplyCapacityToFutureSessionsCommand;
use App\Handlers\ApplyCapacityToFutureSessionsHandler;
use App\Models\Classes;
use Illuminate\Validation\ValidationException;

final class ApplyCapacityToFutureSessionsAction
{
    public function __construct(
        private ApplyCapacityToFutureSessionsHandler $handler,
    ) {}

    /**
     * @return array{affected: int, sessions: array}
     *
     * @throws ValidationException
     */
    public function execute(Classes $class, string $reason): array
    {
        return $this->handler->handle(
            new ApplyCapacityToFutureSessionsCommand(
                classId: $class->id,
                reason: $reason,
            )
        );
    }
}
