<?php
// filePath: app/Http/Controllers/Api/V1/Profile/ProfileController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Profile\ProfileService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profile')]
class ProfileController extends BaseApiController
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    #[Endpoint('Show Profile', description: 'Get the authenticated user profile with preferred language settings.')]
    public function show(Request $request): JsonResponse
    {
        $user = $this->profileService->getProfileWithSettings($request->user()->id);

        return $this->success(new UserResource($user));
    }

    #[Endpoint('Update Profile', description: 'Update the authenticated user profile.')]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile($request->user()->id, $request->validated());

        return $this->success(new UserResource($user), 'Profile updated successfully.');
    }

    #[Endpoint('Delete Account', description: 'Delete the authenticated user account and revoke tokens.')]
    public function destroy(Request $request): JsonResponse
    {
        $this->profileService->deleteAccount($request->user()->id);

        return $this->noContent('Account deleted successfully.');
    }
}
