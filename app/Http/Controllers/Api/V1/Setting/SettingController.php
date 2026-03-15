<?php
// filePath: app/Http/Controllers/Api/V1/Setting/SettingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Setting\UpdateSettingRequest;
use App\Http\Resources\Api\V1\UserSettingResource;
use App\Services\Setting\AppSettingService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Settings')]
class SettingController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settingService
    ) {}

    #[Endpoint('Show Settings', description: 'Retrieve the authenticated user settings including preferred language.')]
    public function show(Request $request): JsonResponse
    {
        $settings = $this->settingService->getUserSettings($request->user()->id);

        return $this->success(new UserSettingResource($settings));
    }

    #[Endpoint('Update Settings', description: 'Update the authenticated user settings.')]
    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $settings = $this->settingService->updateUserSettings(
            $request->user()->id,
            $request->validated()
        );

        return $this->success(
            new UserSettingResource($settings),
            'Settings updated successfully.'
        );
    }
}
