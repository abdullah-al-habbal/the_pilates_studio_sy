<?php

// filePath: app/Services/Instructor/InstructorService.php

declare(strict_types=1);

namespace App\Services\Instructor;

use App\Models\Instructor;
use App\Repositories\Eloquent\Instructor\InstructorEloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstructorService
{
    public function __construct(
        private readonly InstructorEloquentRepository $repository
    ) {}

    public function getInstructor(int $id, array $includes = []): Instructor
    {
        $instructor = $this->repository->find($id, $includes);

        if (! $instructor) {
            throw new ModelNotFoundException("Instructor with ID {$id} not found.");
        }

        return $instructor;
    }

    public function getTopInstructors(int $limit = 5): Collection
    {
        $instructors = $this->repository->getTopByAttendance($limit);

        foreach ($instructors as $instructor) {
            $totalSessions = $instructor->classes->sum(fn ($class) => $class->sessions_count ?? 0);
            $attendedSessions = $instructor->attended_count ?? 0;
            $instructor->avg_attendance = $totalSessions > 0 ? (int) round(($attendedSessions / $totalSessions) * 100) : 0;
        }

        return $instructors;
    }
}
