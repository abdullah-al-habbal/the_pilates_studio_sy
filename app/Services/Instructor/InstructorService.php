<?php
// filePath: app/Services/Instructor/InstructorService.php

declare(strict_types=1);

namespace App\Services\Instructor;

use App\Models\Instructor;
use App\Repositories\Eloquent\Instructor\InstructorEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstructorService
{
    public function __construct(
        private readonly InstructorEloquentRepository $repository
    ) {}

    public function getInstructorWithActiveClasses(int $id): Instructor
    {
        $instructor = $this->repository->findWithActiveClasses($id);

        if (! $instructor) {
            throw new ModelNotFoundException("Instructor with ID {$id} not found.");
        }

        return $instructor;
    }
}
