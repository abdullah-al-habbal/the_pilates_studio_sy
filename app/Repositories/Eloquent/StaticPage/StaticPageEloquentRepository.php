<?php
// filePath: app/Repositories/Eloquent/StaticPage/StaticPageEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\StaticPage;

use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Collection;

class StaticPageEloquentRepository
{
    public function findBySlug(string $slug): ?StaticPage
    {
        return StaticPage::where('slug', $slug)->first();
    }

    /**
     * Get all static pages.
     */
    public function getAll(): Collection
    {
        return StaticPage::all();
    }
}
