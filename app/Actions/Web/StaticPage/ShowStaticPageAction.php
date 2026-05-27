<?php

declare(strict_types=1);

namespace App\Actions\Web\StaticPage;

use App\Services\StaticPage\StaticPageService;
use App\Services\Landing\LandingDataService;
use Illuminate\Contracts\View\View;

class ShowStaticPageAction
{
    public function __construct(
        private readonly StaticPageService $staticPageService,
        private readonly LandingDataService $landingDataService
    ) {}

    public function __invoke(string $slug): View
    {
        $page = $this->staticPageService->findBySlug($slug);
        if (!$page) {
            abort(404);
        }

        $landingData = $this->landingDataService->getLandingData();

        return view('landing.static-page', [
            'page' => $page,
            'landingData' => $landingData,
        ]);
    }
}
