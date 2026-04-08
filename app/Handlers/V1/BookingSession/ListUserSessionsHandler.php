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

    /**
     * Handle listing user sessions by type.
     *
     * @param  string  $type  'upcoming', 'past', or 'both'
     * @return LengthAwarePaginator|array<string, LengthAwarePaginator>
     */
    public function handle(int $userId, string $type, int $perPage): LengthAwarePaginator|array
    {
        return match ($type) {
            'upcoming' => $this->repository->getUpcomingSessionsForUser($userId, $perPage),
            'past' => $this->repository->getPastSessionsForUser($userId, $perPage),
            'both' => $this->repository->getSeparateBothSessionsForUser($userId, $perPage),
            default => $this->repository->getSeparateBothSessionsForUser($userId, $perPage),
        };
    }
}
