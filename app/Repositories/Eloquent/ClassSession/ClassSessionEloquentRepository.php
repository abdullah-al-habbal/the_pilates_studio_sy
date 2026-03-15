<?php
// filePath: app/Repositories/Eloquent/ClassSession/ClassSessionEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClassSession;

use App\Models\ClassSession;
use App\Services\Log\LoggingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClassSessionEloquentRepository
{
    public function __construct(
        private readonly LoggingService $logger
    ) {}

    public function find(int $id): ?ClassSession
    {
        try {
            return DB::transaction(function () use ($id) {
                return ClassSession::find($id);
            });
        } catch (\Exception $e) {
            $this->logger->error('ClassSession find failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function lockForUpdate(int $id): ClassSession
    {
        return DB::transaction(function () use ($id) {
            return ClassSession::lockForUpdate()->findOrFail($id);
        });
    }

    public function availableSpotsCount(int $id): int
    {
        return DB::transaction(function () use ($id) {
            $reserved = ClassSession::findOrFail($id)
                ->bookingSessions()
                ->whereIn('status', ['reserved', 'attended'])
                ->count();

            $session = ClassSession::findOrFail($id);
            return max(0, $session->total_spots - $reserved);
        });
    }

    public function isFull(int $id): bool
    {
        return $this->availableSpotsCount($id) <= 0;
    }
}
