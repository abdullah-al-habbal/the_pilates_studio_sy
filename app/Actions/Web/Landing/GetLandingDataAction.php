<?php

declare(strict_types=1);

namespace App\Actions\Web\Landing;

use App\Services\Landing\LandingDataService;
use App\ValueObjects\Landing\LandingDataVO;

class GetLandingDataAction
{
    public function __construct(private readonly LandingDataService $landingDataService) {}

    public function execute(): LandingDataVO
    {
        return $this->landingDataService->getLandingData();
    }
}
