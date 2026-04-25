<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Repositories\Eloquent\User\UserEloquentRepository;
use Illuminate\Support\Collection;

final readonly class GetUsersListHandler
{
    public function __construct(
        private UserEloquentRepository $repository
    ) {
    }

    public function handle(?int $sessionId = null): Collection
    {
        $users = $sessionId
            ? $this->repository->listExcludingSession($sessionId)
            : $this->repository->list();

        return $users->map(fn($u) => [
            'id' => $u->id,
            'label' => $u->fullname . ($u->phone_number ? ' · ' . $u->phone_number : ''),
        ]);
    }
}
