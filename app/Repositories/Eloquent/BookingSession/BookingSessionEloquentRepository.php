<?php

// app/Repositories/Eloquent/BookingSession/BookingSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\BookingSession;

use App\Enums\BookingSessionStatusEnum;
use App\Models\BookingSession;
use App\Services\Log\LoggingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BookingSessionEloquentRepository
{
    public function __construct(
        private readonly BookingSession $model,
        private readonly LoggingService $logger
    ) {
    }

    public function listUserSessions(int $userId, array $filters = []): LengthAwarePaginator
    {
        return DB::transaction(function () use ($userId, $filters) {
            $this->logger->info('Fetching user booking sessions', ['user_id' => $userId]);

            $query = $this->baseUserSessionsQuery($userId)
                ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
                ->latest();

            return $query->paginate($filters['per_page'] ?? 20);
        });
    }

    public function findByUser(int $userId, int $id): ?BookingSession
    {
        try {
            return DB::transaction(function () use ($userId, $id) {
                $this->logger->info('Finding booking session', ['user_id' => $userId, 'session_id' => $id]);

                return $this->baseUserSessionsQuery($userId)
                    ->find($id);
            });
        } catch (\Exception $e) {
            $this->logger->error('Booking session find failed', [
                'user_id' => $userId,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function create(array $data): BookingSession
    {
        return DB::transaction(function () use ($data) {
            $this->logger->info('Creating booking session', $data);

            return $this->model->create($data);
        });
    }

    public function updateStatus(int $id, string $status): bool
    {
        return DB::transaction(function () use ($id, $status) {
            $this->logger->info('Updating booking session status', ['id' => $id, 'status' => $status]);

            return (bool) $this->model->where('id', $id)->update(['status' => $status]);
        });
    }

    public function existsForUserAndClassSession(int $userId, int $classSessionId): bool
    {
        return $this->model->query()
            ->where('status', BookingSessionStatusEnum::RESERVED->value)
            ->where('class_session_id', $classSessionId)
            ->whereHas('booking', fn($q) => $q->where('user_id', $userId))
            ->exists();
    }

    public function setCancelledAt(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->update(['cancelled_at' => now()]);
    }

    public function find(int $id, bool $lockForUpdate = false): ?BookingSession
    {
        $query = $this->model->where('id', $id);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function getUpcomingSessionsForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        $now = Carbon::now();

        $query = $this->baseUserSessionsQuery($userId)
            ->whereHas('classSession', fn($q) => $this->upcomingCondition($q, $now))
            ->whereIn('status', ['reserved']);

        return $this->paginateWithEager($query, $perPage);
    }

    public function getPastSessionsForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        $now = Carbon::now();

        $query = $this->baseUserSessionsQuery($userId)
            ->whereHas('classSession', fn($q) => $this->pastCondition($q, $now));

        return $this->paginateWithEager($query, $perPage);
    }

    public function getSeparateBothSessionsForUser(int $userId, int $perPage = 20): array
    {
        return [
            'upcoming' => $this->getUpcomingSessionsForUser($userId, $perPage),
            'past' => $this->getPastSessionsForUser($userId, $perPage),
        ];
    }

    private function baseUserSessionsQuery(int $userId): Builder
    {
        return $this->model->query()
            ->whereHas('booking', fn($q) => $q->where('user_id', $userId));
    }

    private function upcomingCondition(Builder $query, Carbon $now): void
    {
        $query->where(function ($sub) use ($now) {
            $sub->whereDate('date', '>', $now->toDateString())
                ->orWhere(function ($inner) use ($now) {
                    $inner->whereDate('date', '=', $now->toDateString())
                        ->whereTime('start_time', '>=', $now->toTimeString());
                });
        });
    }

    private function pastCondition(Builder $query, Carbon $now): void
    {
        $query->where(function ($sub) use ($now) {
            $sub->whereDate('date', '<', $now->toDateString())
                ->orWhere(function ($inner) use ($now) {
                    $inner->whereDate('date', '=', $now->toDateString())
                        ->whereTime('start_time', '<', $now->toTimeString());
                });
        });
    }

    private function paginateWithEager(Builder $query, int $perPage): LengthAwarePaginator
    {
        return $query
            ->with(['classSession.class.instructor', 'classSession.class.primaryImage'])
            ->latest()
            ->paginate($perPage);
    }
}
