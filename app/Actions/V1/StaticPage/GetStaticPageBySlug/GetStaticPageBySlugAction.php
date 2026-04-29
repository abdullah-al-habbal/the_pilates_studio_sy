<?php

declare(strict_types=1);

namespace App\Actions\V1\StaticPage\GetStaticPageBySlug;

use App\Models\StaticPage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final readonly class GetStaticPageBySlugAction
{

    public function __invoke(string $slug): Response
    {
        $page = Cache::remember("static_page_{$slug}", now()->addHours(6), fn() =>
            StaticPage::where('slug', $slug)->firstOrFail()
        );

        return response()
            ->view('static-pages.show', compact('page'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
