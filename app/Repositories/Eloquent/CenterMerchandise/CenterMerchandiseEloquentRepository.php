<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\CenterMerchandise;

use App\Models\CenterMerchandise;

class CenterMerchandiseEloquentRepository
{
    public function __construct(
        private readonly CenterMerchandise $model
    ) {
    }

    public function findForUpdate(int $id): ?CenterMerchandise
    {
        return $this->model->newQuery()->lockForUpdate()->find($id);
    }

    public function decrementStock(int $id, int $quantity): bool
    {
        return (bool) $this->model->where('id', $id)->decrement('stock_quantity', $quantity);
    }

    public function incrementStock(int $id, int $quantity): bool
    {
        return (bool) $this->model->where('id', $id)->increment('stock_quantity', $quantity);
    }
}
