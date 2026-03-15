<?php
// filePath: app/Services/ClassSession/ClassSessionService.php

declare(strict_types=1);

namespace App\Services\ClassSession;

use App\Models\ClassSession;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\Log\LoggingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassSessionService
{
    public function __construct(
        private readonly ClassSessionEloquentRepository $repository,
        private readonly LoggingService $logger
    ) {}

    public function find(int $id): ClassSession
    {
        $this->logger->info('Finding class session', ['id' => $id]);
        $session = $this->repository->find($id);
        return $session ?? throw new ModelNotFoundException;
    }

    public function lockForUpdate(int $id): ClassSession
    {
        $this->logger->info('Locking class session for update', ['id' => $id]);
        return $this->repository->lockForUpdate($id);
    }

    public function hasAvailableSpots(int $id): bool
    {
        $this->logger->info('Checking available spots', ['id' => $id]);
        return !$this->repository->isFull($id);
    }
}
