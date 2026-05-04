<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\CenterMerchandise;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetStoreItemsHandler
{
    public function handle(): Collection
    {
        return CenterMerchandise::with('category')->get();
    }
}
