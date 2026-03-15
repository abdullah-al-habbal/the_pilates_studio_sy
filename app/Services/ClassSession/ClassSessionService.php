<?php
// filePath: app/Services/ClassSession/ClassSessionService.php
declare(strict_types=1);

namespace App\Services\ClassSession;

use App\Models\ClassSession;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClassSessionService
{
    public function __construct(
        private readonly ClassSessionEloquentRepository $repository,
    ) {}

    public function find(int $id, bool $lockForUpdate = false, array $relations = []): ClassSession
    {
        $session = $this->repository->find($id, $lockForUpdate, $relations);

        return $session ?? throw new ModelNotFoundException();
    }

    public function hasAvailableSpots(int $id): bool
    {
        return !$this->repository->isFull($id);
    }
}
