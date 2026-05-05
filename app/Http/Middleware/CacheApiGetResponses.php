<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CacheApiGetResponses
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->header('Cache-Control', 'max-age=30, public');
        }

        return $response;
    }
}
