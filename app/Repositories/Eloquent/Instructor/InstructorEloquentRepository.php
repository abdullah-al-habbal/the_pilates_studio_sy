<?php

// filePath: app/Repositories/Eloquent/Instructor/InstructorEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Instructor;

use App\Enums\AttendanceStatusEnum;
use App\Enums\ClassStatusEnum;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Collection;

class InstructorEloquentRepository
{
    public function getTopByAttendance(int $limit = 5): Collection
    {
        return Instructor::withCount([
            'classes' => function ($query) {
                $query->withCount([
                    'sessions as attended_sessions_count' => function ($q) {
                        $q->whereHas('bookingSessions', fn($bsq) => $bsq->where('attendance_status', AttendanceStatusEnum::ATTENDED));
                    },
                ]);
            },
        ])
            ->get()
            ->map(function ($instructor) {
                $attendedCount = $instructor->classes->sum('attended_sessions_count');
                $instructor->attended_count = $attendedCount;

                return $instructor;
            })
            ->sortByDesc('attended_count')
            ->take($limit);
    }

    public function find(int $id, array $includes = []): ?Instructor
    {
        $includes = $this->normalize($includes);
        $includes = $this->allowed($includes);

        return Instructor::query()
            ->with($this->relations($includes))
            ->find($id);
    }

    private function allowed(array $includes): array
    {
        $allowed = [
            'classes',
            'classes.category',
            'classes.primaryImage',
        ];

        return array_values(array_intersect($includes, $allowed));
    }

    private function normalize(array $includes): array
    {
        $result = [];

        foreach ($includes as $include) {
            $parts = explode('.', $include);

            while ($parts) {
                $result[] = implode('.', $parts);
                array_pop($parts);
            }
        }

        return array_values(array_unique($result));
    }

    private function relations(array $includes): array
    {
        $relations = [];

        if (in_array('classes', $includes)) {
            $relations['classes'] = function ($q) {
                $q->where('status', ClassStatusEnum::ACTIVE)
                    ->latest();
            };
        }

        if (in_array('classes.category', $includes)) {
            $relations['classes.category'] = fn($q) => $q;
        }

        if (in_array('classes.primaryImage', $includes)) {
            $relations['classes.primaryImage'] = fn($q) => $q;
        }

        return $relations;
    }
}
