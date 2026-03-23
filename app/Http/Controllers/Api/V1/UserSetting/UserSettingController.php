<?php
// filePath: app/Http/Controllers/Api/V1/UserSetting/UserSettingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\UserSetting;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UserSetting\UpdateUserSettingRequest;
use App\Http\Resources\Api\V1\UserSettingResource;
use App\Services\UserSetting\UserSettingService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('User Settings')]
class UserSettingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UserSettingService $userSettingService
    ) {}

    #[Endpoint('Show User Settings', description: 'Retrieve the authenticated user settings including preferred language.')]
    public function show(Request $request): JsonResponse
    {
        try {
            $settings = $this->userSettingService->getUserSettings($request->user()->id);

            return $this->success(
                new UserSettingResource($settings),
                SuccessCodeEnum::SUCCESS,
                SuccessCodeEnum::SUCCESS->getMessage()
            );
        } catch (\Exception $e) {
            return $this->error(
                ErrorCodeEnum::BAD_REQUEST,
                'Failed to retrieve settings',
                ErrorCodeEnum::BAD_REQUEST->getStatusCode()
            );
        }
    }

    #[Endpoint('Update User Settings', description: 'Update the authenticated user settings.')]
    public function update(UpdateUserSettingRequest $request): JsonResponse
    {
        try {
            $settings = $this->userSettingService->updateUserSettings(
                $request->user()->id,
                $request->validated()
            );

            return $this->updated(
                new UserSettingResource($settings),
                SuccessCodeEnum::SETTINGS_UPDATED,
                SuccessCodeEnum::SETTINGS_UPDATED->getMessage()
            );
        } catch (\Exception $e) {
            return $this->error(
                ErrorCodeEnum::BAD_REQUEST,
                'Failed to update settings',
                ErrorCodeEnum::BAD_REQUEST->getStatusCode()
            );
        }
    }
}
