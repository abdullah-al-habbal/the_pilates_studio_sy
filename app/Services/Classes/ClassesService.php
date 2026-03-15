<?php
// filePath: app/Services/Classes/ClassesService.php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassesService
{
    public function __construct(
        private readonly ClassesEloquentRepository $repository
    ) {}

    public function listActiveClasses(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->listActiveClasses($perPage);
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
