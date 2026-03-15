<?php
// filePath: app/Repositories/Eloquent/Instructor/InstructorEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Instructor;

use App\Enums\ClassStatusEnum;
use App\Models\Instructor;

class InstructorEloquentRepository
{
    public function findWithActiveClasses(int $id): ?Instructor
    {
        return Instructor::with([
            'classes' => fn ($query) => $query
                ->with('primaryImage', 'category')
                ->where('status', ClassStatusEnum::ACTIVE)
                ->latest(),
        ])->find($id);
    }
}
