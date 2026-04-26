<?php

declare(strict_types=1);

namespace App\Actions\V1\StaticPage\GetStaticPageBySlug;

use App\Models\StaticPage;
use App\Repositories\Eloquent\StaticPage\StaticPageEloquentRepository;

final readonly class GetStaticPageBySlugHandler
{
    public function __construct(
        private StaticPageEloquentRepository $repository
    ) {
    }

    public function handle(string $slug): StaticPage
    {
        return $this->repository->findBySlugOrFail($slug);
    }
}
