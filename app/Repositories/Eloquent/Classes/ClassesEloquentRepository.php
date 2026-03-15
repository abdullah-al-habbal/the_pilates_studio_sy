<?php
// filePath: app/Repositories/Eloquent/Classes/ClassesEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Classes;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassesEloquentRepository
{
    public function listActiveClasses(int $perPage = 20): LengthAwarePaginator
    {
        return Classes::query()
            ->with(['instructor', 'category', 'primaryImage'])
            ->where('status', ClassStatusEnum::ACTIVE)
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
