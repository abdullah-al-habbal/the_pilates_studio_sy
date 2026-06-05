<?php

declare(strict_types=1);

namespace App\Actions\Web\Landing;

use App\Http\Controllers\Controller;
use App\Services\Landing\LandingDataService;
use Illuminate\Contracts\View\View;

class GetLandingDataAction extends Controller
{
    public function __construct(
        private readonly LandingDataService $landingDataService
    ) {}

    public function execute(): View
    {
        $landingData = $this->landingDataService->getLandingData();

        if (empty($landingData->settings->heroImage) || empty($landingData->settings->siteName)) {
            abort(503, 'Landing page is not configured yet.');
        }

        return view('landing.index', compact('landingData'));
    }
}
