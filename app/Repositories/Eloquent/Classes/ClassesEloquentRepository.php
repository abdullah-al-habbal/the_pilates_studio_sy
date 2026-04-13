<?php

// filePath: app/Repositories/Eloquent/Classes/ClassesEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Classes;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use App\Models\Classes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ClassesEloquentRepository
{
    public function __construct(
        private readonly Classes $model
    ) {}

    public function queryActiveClasses(
        ?string $date,
        ?string $startAfter,
        ?string $startBefore,
        ?int $categoryId,
        ?int $instructorId,
        int $perPage
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['category', 'instructor', 'primaryImage'])
            ->where('status', ClassStatusEnum::ACTIVE)
            ->when($categoryId, fn ($q, $id) => $q->where('class_category_id', $id))
            ->when($instructorId, fn ($q, $id) => $q->where('instructor_id', $id));

        if ($date) {
            $query->whereHas('sessions', function ($q) use ($date, $startAfter, $startBefore) {
                $q->whereDate('date', $date)
                    ->when($startAfter, fn ($sq, $time) => $sq->where('start_time', '>=', $time))
                    ->when($startBefore, fn ($sq, $time) => $sq->where('start_time', '<=', $time));
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getPopularClassesSummary(int $limit = 5, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        return $this->model->newQuery()
            ->with(['sessions' => function ($query) use ($startDate, $endDate) {
                $query->when($startDate, fn ($q) => $q->where('date', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->where('date', '<=', $endDate))
                    ->with('bookingSessions');
            }])
            ->get()
            ->map(function ($class) {
                $totalSessions = $class->sessions->count();
                $totalAttendance = $class->sessions->sum(function ($session) {
                    return $session->bookingSessions->count();
                });

                return (object) [
                    'id' => $class->id,
                    'title' => $class->title,
                    'sessions_count' => $totalSessions,
                    'total_attendance' => $totalAttendance,
                    'avg_attendance' => $totalSessions > 0 ? round($totalAttendance / $totalSessions, 1) : 0,
                ];
            })
            ->filter(fn ($item) => $item->total_attendance > 0)
            ->sortByDesc('total_attendance')
            ->take($limit);
    }

    public function findById(int $id): ?Classes
    {
        return $this->model->newQuery()
            ->with([
                'instructor',
                'category',
                'images',
                'recurrencePattern',
                'sessions' => function ($query) {
                    $query->whereDate('date', '>=', now()->toDateString())
                        ->orderBy('date')
                        ->orderBy('start_time');
                },
            ])->find($id);
    }
}
