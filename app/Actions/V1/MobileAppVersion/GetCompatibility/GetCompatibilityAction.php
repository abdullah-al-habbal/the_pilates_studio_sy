<?php
// filePath: app/Actions/V1/MobileAppVersion/GetCompatibility/GetCompatibilityAction.php

declare(strict_types=1);

namespace App\Actions\V1\MobileAppVersion\GetCompatibility;

use App\Enums\Api\ErrorCodeEnum;
use App\Http\Requests\Api\V1\MobileAppVersion\GetCompatibilityRequest;
use App\Services\MobileAppVersion\AppVersionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile App Version')]
final readonly class GetCompatibilityAction
{
    use ApiResponseTrait;

    public function __construct(
        private AppVersionService $service,
    ) {
    }

    #[Endpoint('Get mobile app version compatibility', description: 'Returns the compatibility status of a given mobile app version.')]
    public function __invoke(GetCompatibilityRequest $request): JsonResponse
    {
        try {
            $appName = $request->validatedAppName();
            $platform = $request->validatedPlatform();
            $version = $request->validatedVersion();

            $compat = $this->service->getCompatibility($appName, $platform, $version);

            $deviceId = $request->header('X-Device-ID');
            $sessionId = $deviceId ?? Str::uuid()->toString();

            if ($compat['update_required']) {
                return $this->error(
                    ErrorCodeEnum::APP_VERSION_OUTDATED,
                    $compat['message'] ?? 'A newer version of the app is required.',
                    426,
                    null,
                    ['store_url' => $compat['store_url']]
                );
            }

            return $this->success([
                'session_id' => $sessionId,
                'update_required' => $compat['update_required'],
                'update_available' => $compat['update_available'],
                'message' => $compat['message'],
                'store_url' => $compat['store_url'],
                'min_version' => $compat['min_version'],
                'latest_version' => $compat['latest_version'],
            ]);

        } catch (RuntimeException $e) {
            Log::error('Mobile app version configuration error in action', [
                'message' => $e->getMessage(),
                'app_name' => $request->validatedAppName()->value,
                'platform' => $request->validatedPlatform()->value,
                'version' => $request->validatedVersion(),
            ]);

            return $this->error(
                ErrorCodeEnum::SERVER_CONFIGURATION_ERROR,
                $e->getMessage(),
                $e->getCode() ?: 503
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error in compatibility check', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                'An unexpected error occurred. Please try again later.',
                500
            );
        }
    }
}
