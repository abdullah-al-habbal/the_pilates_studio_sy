<?php

// filePath: app/Services/ClassSession/ClassSessionService.php

declare(strict_types=1);

namespace App\Services\ClassSession;

use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\BookingSession\BookingSessionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassSessionService
{
    public function __construct(
        private readonly ClassSessionEloquentRepository $repository
    ) {}

    public function querySessions(
        ?string $date = null,
        ?string $dateAfter = null,
        ?string $dateBefore = null,
        ?string $startAfter = null,
        ?int $classId = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->repository->queryUpcomingSessions(
            date: $date,
            dateAfter: $dateAfter,
            dateBefore: $dateBefore,
            startAfter: $startAfter,
            classId: $classId,
            perPage: $perPage
        );
    }

    public function reserve(int $bookingId, int $classSessionId): BookingSession
    {
        return app(BookingSessionService::class)->reserve($bookingId, $classSessionId);
    }

    public function listUpcomingSessions(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->queryUpcomingSessions(
            date: null,
            dateAfter: null,
            dateBefore: null,
            startAfter: null,
            classId: null,
            perPage: $perPage
        );
    }

    public function find(int $id, bool $lockForUpdate = false): ClassSession
    {
        $session = $lockForUpdate
            ? ClassSession::lockForUpdate()->find($id)
            : $this->repository->findById($id);

        if (! $session) {
            throw new ModelNotFoundException("Class session with ID {$id} not found.");
        }

        return $session;
    }

    public function hasAvailableSpots(int $id): bool
    {
        $session = $this->repository->findById($id);

        return $session && $session->available_spots > 0;
    }

    public function getSessionById(int $id): ClassSession
    {
        $session = $this->repository->findById($id);

        if (! $session) {
            throw new ModelNotFoundException("Class session with ID {$id} not found.");
        }

        return $session;
    }
}
