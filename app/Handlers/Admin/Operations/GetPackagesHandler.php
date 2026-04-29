<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetPackagesHandler
{
    /**
     * List all active purchaseable packages.
     */
    public function handle(): Collection
    {
        return Package::where('is_active', true)->get();
    }
}
