<?php

// filePath: app/Http/Controllers/Api/V1/Setting/SettingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Setting\UpdateSettingRequest;
use App\Http\Resources\Api\V1\UserSettingResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Settings')]
class SettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $request->user()
            ->settings()
            ->with('preferredLanguage')
            ->firstOrCreate(['user_id' => $request->user()->id]);

        return $this->success(new UserSettingResource($settings));
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $settings = $request->user()
            ->settings()
            ->firstOrCreate(['user_id' => $request->user()->id]);

        $settings->update($request->validated());

        return $this->success(
            new UserSettingResource($settings->fresh()->load('preferredLanguage')),
            'Settings updated successfully.',
        );
    }
}
