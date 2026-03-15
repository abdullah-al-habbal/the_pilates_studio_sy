<?php
// filePath: app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassSession;

use App\Models\ClassSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassSessionEloquentRepository
{
    public function listUpcomingSessions(int $perPage = 20): LengthAwarePaginator
    {
        return ClassSession::query()
            ->with(['class.instructor', 'class.primaryImage'])
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate($perPage);
    }

    public function findById(int $id): ?ClassSession
    {
        return ClassSession::with([
            'class.instructor',
            'class.category',
            'class.primaryImage',
        ])->find($id);
    }
}
