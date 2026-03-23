<?php

// filePath: app/Http/Middleware/MobileAppVersion/CheckAppVersionMiddleware.php

declare(strict_types=1);

namespace App\Http\Middleware\MobileAppVersion;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Services\MobileAppVersion\AppVersionService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class CheckAppVersionMiddleware
{
    use ApiResponseTrait;

    public function __construct(
        private AppVersionService $service,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkipVersionCheck($request)) {
            return $next($request);
        }

        if (! $this->hasRequiredHeaders($request)) {
            return $this->error(
                ErrorCodeEnum::MISSING_REQUIRED_HEADERS,
                'Missing required headers: X-App-Platform and X-App-Version are mandatory.',
                400
            );
        }

        $appName = $this->resolveAppName($request);
        $platform = $this->resolvePlatform($request);

        if ($platform === null) {
            return $this->error(
                ErrorCodeEnum::INVALID_PLATFORM,
                'Invalid X-App-Platform header. Must be "android" or "ios".',
                400
            );
        }

        $clientVersion = $this->getClientVersion($request);

        $compat = $this->service->getCompatibility($appName, $platform, $clientVersion);

        if ($compat['update_required']) {
            return $this->error(
                ErrorCodeEnum::APP_VERSION_OUTDATED,
                $compat['message'] ?? 'A newer version of the app is required.',
                426,
                null,
                ['store_url' => $compat['store_url']]
            );
        }

        $request->attributes->set('app_version_compat', $compat);

        return $next($request);
    }

    private function shouldSkipVersionCheck(Request $request): bool
    {
        $skipRoutes = [
            'api/v1/public/auth/login',
            'api/v1/public/auth/register',
            'api/v1/public/auth/email/verify',
            'api/v1/public/auth/email/resend',
        ];

        $currentPath = $request->path();

        foreach ($skipRoutes as $route) {
            if (str_starts_with($currentPath, $route)) {
                return true;
            }
        }

        return false;
    }

    private function hasRequiredHeaders(Request $request): bool
    {
        return $request->hasHeader('X-App-Platform')
            && $request->hasHeader('X-App-Version');
    }

    private function resolveAppName(Request $request): AppNameEnum
    {
        $header = $request->header('X-App-Name', AppNameEnum::CUSTOMER->value);

        try {
            return AppNameEnum::from($header);
        } catch (\ValueError) {
            return AppNameEnum::CUSTOMER;
        }
    }

    private function resolvePlatform(Request $request): ?MobilePlatformEnum
    {
        $platformHeader = $request->header('X-App-Platform');

        if ($platformHeader === null) {
            return null;
        }

        try {
            return MobilePlatformEnum::from(strtolower($platformHeader));
        } catch (\ValueError) {
            return null;
        }
    }

    private function getClientVersion(Request $request): string
    {
        $version = $request->header('X-App-Version', '0.0.0');

        return $version;
    }
}
