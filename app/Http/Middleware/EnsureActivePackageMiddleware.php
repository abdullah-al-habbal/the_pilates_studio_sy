<?php

// app/Http/Middleware/EnsureActivePackageMiddleware.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Api\ErrorCodeEnum;
use App\Services\Package\PackageService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePackageMiddleware
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PackageService $packageService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->packageService->hasActivePackage()) {
            return $this->error(
                ErrorCodeEnum::SERVER_CONFIGURATION_ERROR,
                'No active package configured. Please contact support.',
                503
            );
        }

        return $next($request);
    }
}
