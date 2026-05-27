<?php

// filePath: app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassSession;

use App\Models\ClassSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ClassSessionEloquentRepository
{
    public function __construct(
        private readonly ClassSession $model
    ) {
    }

    public function getSessionsBetween(string $startDate, string $endDate): Collection
    {
        return $this->model->newQuery()
            ->with(['class.instructor'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'scheduled')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function queryUpcomingSessions(
        ?string $date,
        ?string $dateAfter,
        ?string $dateBefore,
        ?string $startAfter,
        ?int $classId,
        int $perPage
    ): LengthAwarePaginator {
        return $this->model->newQuery()
            ->with(['class.instructor', 'class.primaryImage', 'class.category'])
            ->when($date, fn($q) => $q->whereDate('date', $date))
            ->when($dateAfter, fn($q) => $q->whereDate('date', '>=', $dateAfter))
            ->when($dateBefore, fn($q) => $q->whereDate('date', '<=', $dateBefore))
            ->when($startAfter, fn($q, $time) => $q->where('start_time', '>=', $time))
            ->when($classId, fn($q, $id) => $q->where('class_id', $id))
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate($perPage);
    }

    public function getSchedulerQuery(): Builder
    {
        return $this->model->newQuery()
            ->with(['class.instructor', 'bookingSessions'])
            ->latest('date');
    }

    public function findById(int $id): ?ClassSession
    {
        return $this->model->newQuery()
            ->with([
                'class.instructor',
                'class.category',
                'class.primaryImage',
            ])->find($id);
    }

    public function getSessionsByDate($date): Collection
    {
        return $this->model->newQuery()
            ->whereDate('date', $date)
            ->where('status', 'scheduled')
            ->with([
                'class.instructor',
                'bookingSessions.booking.user.bookings' => fn($q) => $q->where('status', 'active')->where('remaining_credits', '>', 0),
            ])
            ->orderBy('start_time')
            ->get();
    }
    public function findOrFailForUpdate(int $id): ClassSession
    {
        return $this->model->newQuery()->lockForUpdate()->findOrFail($id);
    }

    public function paginateDailySessions(string $date, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with([
                'class.instructor',
                'bookingSessions',
            ])
            ->whereDate('date', $date)
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findWithDetails(int $id): ClassSession
    {
        return $this->model->newQuery()
            ->with([
                'class.instructor',
                'bookingSessions.booking.user',
            ])->findOrFail($id);
    }

    public function countReserved(int $id): int
    {
        return $this->model->newQuery()
            ->findOrFail($id)
            ->bookingSessions()
            ->count();
    }

    public function findForUpdate(int $id): ?ClassSession
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    public function countUpcomingFullSessions(): int
    {
        $now = now();
        return $this->model->newQuery()
            ->where(function ($query) use ($now) {
                $query->whereDate('date', '>', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('date', '=', $now->toDateString())
                            ->whereTime('start_time', '>', $now->toTimeString());
                    });
            })
            ->whereHas('bookingSessions', null, '>=', DB::raw('class_sessions.total_spots'))
            ->count();
    }

    public function getAvailableSpots(int $id): int
    {
        $session = $this->model->newQuery()->find($id);
        if (!$session) {
            return 0;
        }

        $capacity = (int) ($session->total_spots ?? 0);
        if ($capacity <= 0) {
            return PHP_INT_MAX;
        }

        $reserved = $session->bookingSessions()->count();
        return max(0, $capacity - $reserved);
    }
}
