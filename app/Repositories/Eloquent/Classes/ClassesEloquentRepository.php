<?php

// filePath: app/Repositories/Eloquent/Classes/ClassesEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Classes;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
