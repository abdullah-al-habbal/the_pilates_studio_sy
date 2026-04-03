<?php

// filePath: app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassSession;

use App\Models\ClassSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassSessionEloquentRepository
{
    public function queryUpcomingSessions(
        ?string $date,
        ?string $dateAfter,
        ?string $dateBefore,
        ?string $startAfter,
        ?int $classId,
        int $perPage
    ): LengthAwarePaginator {
        return ClassSession::query()
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

    public function findById(int $id): ?ClassSession
    {
        return ClassSession::with([
            'class.instructor',
            'class.category',
            'class.primaryImage',
        ])->find($id);
    }
}
