<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\MerchandiseOrder;

use App\Models\MerchandiseOrder;

class MerchandiseOrderEloquentRepository
{
    public function __construct(
        private readonly MerchandiseOrder $model
    ) {
    }

    public function create(array $data): MerchandiseOrder
    {
        return $this->model->create($data);
    }

    public function findOrFail(int $id): MerchandiseOrder
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }
}
