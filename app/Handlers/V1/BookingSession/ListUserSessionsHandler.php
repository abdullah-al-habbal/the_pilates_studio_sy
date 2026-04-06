<?php

declare(strict_types=1);

namespace App\Handlers\V1\BookingSession;

use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListUserSessionsHandler
{
    public function __construct(
        private BookingSessionEloquentRepository $repository,
    ) {}

    public function handle(int $userId, string $type, int $perPage): LengthAwarePaginator
    {
        return match ($type) {
            'upcoming' => $this->repository->getUpcomingSessionsForUser($userId, $perPage),
            'past' => $this->repository->getPastSessionsForUser($userId, $perPage),
            'both' => $this->repository->getBothSessionsForUser($userId, $perPage),
            default => $this->repository->getBothSessionsForUser($userId, $perPage),
        };
    }
}
