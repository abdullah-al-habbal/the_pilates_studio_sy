<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassCategory;

use App\Enums\BookingSessionStatusEnum;
use App\Models\ClassCategory;
use Illuminate\Database\Eloquent\Collection;

class ClassCategoryEloquentRepository
{
    public function getTopByAttendance(int $limit = 3): Collection
    {
        return ClassCategory::with([
            'classes' => function ($query) {
                $query->withCount([
                    'sessions as attended_sessions_count' => function ($q) {
                        $q->whereHas('bookingSessions', fn ($bsq) => $bsq->where('status', BookingSessionStatusEnum::ATTENDED));
                    },
                ]);
            },
        ])
            ->withCount('classes')
            ->get()
            ->sortByDesc(fn ($category) => $category->classes->sum('attended_sessions_count'))
            ->take($limit);
    }
}
