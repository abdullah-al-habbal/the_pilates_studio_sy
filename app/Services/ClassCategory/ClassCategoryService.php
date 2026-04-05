<?php

declare(strict_types=1);

namespace App\Services\ClassCategory;

use App\Repositories\Eloquent\ClassCategory\ClassCategoryEloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class ClassCategoryService
{
    public function __construct(
        private readonly ClassCategoryEloquentRepository $repository
    ) {}

    public function getTopCategories(int $limit = 3): Collection
    {
        return $this->repository->getTopByAttendance($limit);
    }
}
