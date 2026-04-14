<?php

// filePath: app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassSession;

use App\Models\ClassSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ClassSessionEloquentRepository
{
    public function __construct(
        private readonly ClassSession $model
    ) {}

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
            ->when($date, fn ($q) => $q->whereDate('date', $date))
            ->when($dateAfter, fn ($q) => $q->whereDate('date', '>=', $dateAfter))
            ->when($dateBefore, fn ($q) => $q->whereDate('date', '<=', $dateBefore))
            ->when($startAfter, fn ($q, $time) => $q->where('start_time', '>=', $time))
            ->when($classId, fn ($q, $id) => $q->where('class_id', $id))
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
                'bookingSessions.booking.user.bookings' => fn ($q) => $q->where('status', 'active')->where('remaining_credits', '>', 0),
            ])
            ->orderBy('start_time')
            ->get();
    }
}
