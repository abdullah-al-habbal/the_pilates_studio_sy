<?php
// filePath: app/Repositories/Eloquent/StaticPage/StaticPageEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\StaticPage;

use App\Models\StaticPage;

class StaticPageEloquentRepository
{
    public function findBySlug(string $slug): ?StaticPage
    {
        return StaticPage::where('slug', $slug)->first();
    }
}
