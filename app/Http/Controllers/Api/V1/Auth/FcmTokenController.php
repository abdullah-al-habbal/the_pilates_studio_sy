<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\Api\SuccessCodeEnum;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\StoreFcmTokenRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('authentication')]
final class FcmTokenController extends BaseApiController
{
    public function store(StoreFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            ['fcm_token' => $request->validated('fcm_token')]
        );

        return $this->success(null, SuccessCodeEnum::SUCCESS, 'FCM token stored successfully.');
    }
    public function destroy(Request $request): JsonResponse
    {
        $request->user()
            ->settings()
            ->whereNotNull('fcm_token')
            ->update(['fcm_token' => null]);

        return $this->noContent(SuccessCodeEnum::SUCCESS, 'FCM token removed.');
    }
}