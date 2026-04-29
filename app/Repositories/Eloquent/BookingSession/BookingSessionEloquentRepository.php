<?php

// app/Repositories/Eloquent/BookingSession/BookingSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\BookingSession;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Models\BookingSession;
use App\Services\Log\LoggingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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
                ->with(['classSession.class'])
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
    public function markAttended(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->update([
            'attendance_status' => AttendanceStatusEnum::ATTENDED,
            'attended_at'       => now(),
        ]);
    }

    public function markMissed(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->update([
            'attendance_status' => AttendanceStatusEnum::MISSED,
            'attended_at'       => null,
        ]);
    }

    public function countAttended(): int
    {
        return $this->model->where('attendance_status', AttendanceStatusEnum::ATTENDED)->count();
    }

    public function countMissed(): int
    {
        return $this->model->where('attendance_status', AttendanceStatusEnum::MISSED)->count();
    }

    public function countMissedForMonth(int $month, int $year): int
    {
        return $this->model->where('attendance_status', AttendanceStatusEnum::MISSED)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();
    }

    public function countCancelled(): int
    {
        return $this->model->where('status', BookingSessionStatusEnum::CANCELLED)->count();
    }

    public function getAttendanceTrend(int $days = 30): Collection
    {
        $startDate = now()->subDays($days)->startOfDay();
        return $this->model->where('attendance_status', AttendanceStatusEnum::ATTENDED)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');
    }

    public function totalSessionsCount(): int
    {
        return $this->model->count();
    }

    public function getCancellationRateByUser(int $limit = 20): Collection
    {
        return DB::table('booking_sessions')
            ->join('bookings', 'booking_sessions.booking_id', '=', 'bookings.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->where('booking_sessions.status', BookingSessionStatusEnum::CANCELLED->value)
            ->selectRaw('
                users.id,
                users.fullname,
                users.phone_number,
                COUNT(*) as cancellation_count,
                MAX(booking_sessions.cancelled_at) as last_cancelled_at
            ')
            ->groupBy('users.id', 'users.fullname', 'users.phone_number')
            ->orderByDesc('cancellation_count')
            ->limit($limit)
            ->get();
    }
}
