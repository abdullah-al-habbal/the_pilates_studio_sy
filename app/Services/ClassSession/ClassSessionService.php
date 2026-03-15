<?php
// filePath: app/Services/ClassSession/ClassSessionService.php

declare(strict_types=1);

namespace App\Services\ClassSession;

use App\Models\ClassSession;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassSessionService
{
    public function __construct(
        private readonly ClassSessionEloquentRepository $repository
    ) {}

    public function listUpcomingSessions(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->listUpcomingSessions($perPage);
    }

    public function getSessionById(int $id): ClassSession
    {
        $session = $this->repository->findById($id);

        if (! $session) {
            throw new ModelNotFoundException("Class session with ID {$id} not found.");
        }

        return $session;
    }
}
