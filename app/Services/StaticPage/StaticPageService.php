<?php
// filePath: app/Services/StaticPage/StaticPageService.php

declare(strict_types=1);

namespace App\Services\StaticPage;

use App\Models\StaticPage;
use App\Repositories\Eloquent\StaticPage\StaticPageEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StaticPageService
{
    public function __construct(
        private readonly StaticPageEloquentRepository $repository
    ) {}

    public function getPageBySlug(string $slug): StaticPage
    {
        $page = $this->repository->findBySlug($slug);

        if (! $page) {
            throw new ModelNotFoundException("Static page with slug '{$slug}' not found.");
        }

        return $page;
    }
}
