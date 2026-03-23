<?php
// filePath: app/Actions/V1/MobileAppVersion/GetCompatibility/GetCompatibilityAction.php

declare(strict_types=1);

namespace App\Actions\V1\MobileAppVersion\GetCompatibility;

use App\Http\Requests\Api\V1\MobileAppVersion\GetCompatibilityRequest;
use App\Services\MobileAppVersion\AppVersionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Mobile App Version')]
final readonly class GetCompatibilityAction
{
    use ApiResponseTrait;

    public function __construct(
        private AppVersionService $service,
    ) {}

    #[Endpoint('Get mobile app version compatibility', description: 'Returns the compatibility status of a given mobile app version.')]
    public function __invoke(GetCompatibilityRequest $request): JsonResponse
    {
        $appName  = $request->validatedAppName();
        $platform = $request->validatedPlatform();
        $version  = $request->validatedVersion();

        $compat = $this->service->getCompatibility($appName, $platform, $version);

        return $this->success($compat);
    }
}
