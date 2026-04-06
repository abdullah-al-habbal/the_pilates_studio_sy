<?php

// app/Http/Middleware/EnsureActiveBookingMiddleware.php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Api\ErrorCodeEnum;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBookingMiddleware
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $activeBooking = $user?->activeCreditBooking;

        if (! $activeBooking || $activeBooking->remaining_credits <= 0) {
            return $this->error(
                ErrorCodeEnum::FORBIDDEN,
                'No active booking with credits available.',
                403
            );
        }

        $request->attributes->set('active_booking', $activeBooking);

        return $next($request);
    }
}
