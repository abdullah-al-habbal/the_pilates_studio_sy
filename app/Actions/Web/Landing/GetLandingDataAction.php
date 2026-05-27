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
        return view('landing.index', compact('landingData'));
    }
}
