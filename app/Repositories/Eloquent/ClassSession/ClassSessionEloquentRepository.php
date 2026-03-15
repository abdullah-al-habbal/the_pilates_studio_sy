<?php
// filePath: app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassSession;

use App\Models\ClassSession;

class ClassSessionEloquentRepository
{
    public function find(int $id, bool $lockForUpdate = false, array $relations = []): ?ClassSession
    {
        $query = ClassSession::query();

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function availableSpotsCount(int $id): int
    {
        $reserved = ClassSession::findOrFail($id)
            ->bookingSessions()
            ->whereIn('status', ['reserved', 'attended'])
            ->count();

        $session = ClassSession::findOrFail($id);
        return max(0, $session->total_spots - $reserved);
    }

    public function isFull(int $id): bool
    {
        return $this->availableSpotsCount($id) <= 0;
    }
}
