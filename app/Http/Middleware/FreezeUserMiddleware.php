<?php
declare(strict_types=1);
namespace App\Http\Middleware;
use App\Enums\Api\ErrorCodeEnum;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FreezeUserMiddleware
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isFrozen()) {
            return $this->error(ErrorCodeEnum::FROZEN_USER);
        }
        return $next($request);
    }
}
