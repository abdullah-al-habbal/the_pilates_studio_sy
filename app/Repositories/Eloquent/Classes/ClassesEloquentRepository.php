<?php

// filePath: app/Repositories/Eloquent/Classes/ClassesEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Classes;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassesEloquentRepository
{
    public function queryActiveClasses(
        ?string $date,
        ?string $startAfter,
        ?string $startBefore,
        ?int $categoryId,
        ?int $instructorId,
        int $perPage
    ): LengthAwarePaginator {
        return Classes::query()
            ->with(['instructor', 'category', 'primaryImage'])
            ->where('status', ClassStatusEnum::ACTIVE)
            ->when($date, fn ($q) => $q->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date))
            ->when($startAfter, fn ($q, $time) => $q->where('start_time', '>=', $time))
            ->when($startBefore, fn ($q, $time) => $q->where('start_time', '<=', $time))
            ->when($categoryId, fn ($q, $id) => $q->where('class_category_id', $id))
            ->when($instructorId, fn ($q, $id) => $q->where('instructor_id', $id))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Classes
    {
        return Classes::with([
            'instructor',
            'category',
            'images',
            'recurrencePattern',
            'sessions',
        ])->find($id);
    }
}
