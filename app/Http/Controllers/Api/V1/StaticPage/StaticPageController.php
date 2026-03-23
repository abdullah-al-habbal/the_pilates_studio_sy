<?php
// filePath: app/Http/Controllers/Api/V1/StaticPage/StaticPageController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\StaticPage;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\StaticPageResource;
use App\Services\StaticPage\StaticPageService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Static Pages')]
class StaticPageController extends BaseApiController
{
    public function __construct(
        private readonly StaticPageService $staticPageService
    ) {}

    #[Endpoint('Get static page by slug', description: 'Returns a static page by its slug.')]
    public function showBySlug(string $slug): JsonResponse
    {
        $page = $this->staticPageService->getPageBySlug($slug);

        return $this->success(new StaticPageResource($page));
    }
}
