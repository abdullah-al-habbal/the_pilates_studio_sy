<?php

// filePath: app/Http/Controllers/Api/V1/StaticPage/StaticPageController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\StaticPage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StaticPageResource;
use App\Models\StaticPage;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Static Pages')]
class StaticPageController extends Controller
{
    #[Endpoint('Get static page by slug', description: 'Returns a static page by its slug.')]
    public function showBySlug(string $slug): JsonResponse
    {
        $page = StaticPage::where('slug', $slug)->firstOrFail();

        return $this->success(new StaticPageResource($page));
    }
}
