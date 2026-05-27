<?php

// filePath: app/Services/Classes/ClassesService.php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Models\Classes;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassesService
{
    public function __construct(
        private readonly ClassesEloquentRepository $repository
    ) {}

    public function getActiveClassesForLanding(): Collection
    {
        return $this->repository->getActiveClassesForLanding();
    }

    public function queryClasses(
        ?string $date = null,
        ?string $startAfter = null,
        ?string $startBefore = null,
        ?int $categoryId = null,
        ?int $instructorId = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->repository->queryActiveClasses(
            date: $date,
            startAfter: $startAfter,
            startBefore: $startBefore,
            categoryId: $categoryId,
            instructorId: $instructorId,
            perPage: $perPage
        );
    }

    public function getClassById(int $id): Classes
    {
        $class = $this->repository->findById($id);

        if (! $class) {
            throw new ModelNotFoundException("Class with ID {$id} not found.");
        }

        return $class;
    }
}
