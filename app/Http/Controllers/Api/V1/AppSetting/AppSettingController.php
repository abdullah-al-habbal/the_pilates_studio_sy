<?php

// filePath: app/Http/Controllers/Api/V1/AppSetting/AppSettingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AppSetting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppSettingResource;
use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AppSettingController extends Controller
{
    use ApiResponse;

    public function showByKey(string $key): JsonResponse
    {
        $setting = AppSetting::where('key', $key)->firstOrFail();

        return $this->success(new AppSettingResource($setting));
    }
}
