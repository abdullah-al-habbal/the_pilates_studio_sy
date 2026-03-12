<?php

// filePath: app/Http/Controllers/Api/V1/StaticPage/StaticPageController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\StaticPage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StaticPageResource;
use App\Models\StaticPage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class StaticPageController extends Controller
{
    use ApiResponse;

    public function showBySlug(string $slug): JsonResponse
    {
        $page = StaticPage::where('slug', $slug)->firstOrFail();

        return $this->success(new StaticPageResource($page));
    }
}
