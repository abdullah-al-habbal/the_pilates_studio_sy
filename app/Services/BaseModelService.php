<?php
// filePath: app/Services/BaseModelService.php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\Eloquent\BaseEloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseModelService
{
    public function __construct(
        private readonly BaseEloquentRepository $repository
    ) {}

    /**
     * Human-readable model label for exception messages (e.g. 'Instructor').
     */
    abstract protected function modelLabel(): string;

    /**
     * @param  int|string $id
     * @param  string[]   $includes
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function getById(int|string $id, array $includes = []): Model
    {
        $model = $this->repository->find($id, $includes);

        if ($model === null) {
            throw new ModelNotFoundException(
                sprintf('%s with ID %s not found.', $this->modelLabel(), $id)
            );
        }

        return $model;
    }
}
