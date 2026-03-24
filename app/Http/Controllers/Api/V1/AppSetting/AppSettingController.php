<?php
// filePath: app/Http/Controllers/Api/V1/AppSetting/AppSettingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AppSetting;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppSettingResource;
use App\Services\AppSetting\AppSettingService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\JsonResponse;

#[Group('app_settings')]
class AppSettingController extends BaseApiController
{
    public function __construct(
        private readonly AppSettingService $appSettingService
    ) {}

    #[Endpoint('List all app settings', description: 'Returns a list of all app settings.')]
    public function index(): JsonResponse
    {
        $settings = $this->appSettingService->getAll();
        return $this->success(AppSettingResource::collection($settings));
    }


    #[Endpoint('Get app setting by key', description: 'Returns an app setting by its key.')]
    #[PathParameter('key', description: 'The setting key (e.g., "app_name", "contact_email")', type: 'string')]
    public function showByKey(string $key): JsonResponse
    {
        $setting = $this->appSettingService->getByKey($key);

        return $this->success(new AppSettingResource($setting));
    }
}
