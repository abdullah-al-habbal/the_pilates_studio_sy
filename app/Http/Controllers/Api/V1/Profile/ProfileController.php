<?php

// filePath: app/Http/Controllers/Api/V1/Profile/ProfileController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Profile')]
class ProfileController extends Controller
{
    #[Endpoint('Show Profile', description: 'Get the authenticated user profile with preferred language settings.')]
    public function show(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()->load('settings.preferredLanguage')),
        );
    }

    #[Endpoint('Update Profile', description: 'Update the authenticated user profile.')]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return $this->success(
            new UserResource($request->user()->fresh()),
            'Profile updated successfully.',
        );
    }

    #[Endpoint('Delete Account', description: 'Delete the authenticated user account and revoke tokens.')]
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();
        $user->update(['deleted_by' => $user->id]);
        $user->delete();

        return $this->noContent('Account deleted successfully.');
    }
}
