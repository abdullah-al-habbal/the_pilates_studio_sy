<?php

// app/Http/Middleware/EnsureActivePackageMiddleware.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Api\ErrorCodeEnum;
use App\Models\Package;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePackageMiddleware
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $hasActivePackage = cache()->remember('has_active_package', 300, function () {
            return Package::where('is_active', true)->exists();
        });

        if (! $hasActivePackage) {
            return $this->error(
                ErrorCodeEnum::SERVER_CONFIGURATION_ERROR,
                'No active package configured. Please contact support.',
                503
            );
        }

        return $next($request);
    }
}
