<?php
// filePath: bootstrap/app.php

declare(strict_types=1);

use App\Http\Middleware\MobileAppVersion\CheckAppVersionMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Auth\Middleware\{
    Authenticate,
    AuthenticateWithBasicAuth,
    Authorize,
    EnsureEmailIsVerified,
    RedirectIfAuthenticated,
    RequirePassword
};
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Foundation\Http\Middleware\{
    HandlePrecognitiveRequests,
    InvokeDeferredCallbacks,
    ValidateCsrfToken
};
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\{
    SubstituteBindings,
    ThrottleRequests,
    ValidateSignature
};
use Illuminate\Session\Middleware\{AuthenticateSession, StartSession};
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web/web.php',
        api: __DIR__ . '/../routes/api/api.php',
        commands: __DIR__ . '/../routes/console/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SetLocaleMiddleware::class);

        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.sanctum' => EnsureFrontendRequestsAreStateful::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'precognitive' => HandlePrecognitiveRequests::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            CheckAppVersionMiddleware::class,
            SubstituteBindings::class,
        ]);

        $middleware->web(append: []);

        $middleware->priority([
            InvokeDeferredCallbacks::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            EnsureFrontendRequestsAreStateful::class,
            SubstituteBindings::class,
            ThrottleRequests::class,
            Authenticate::class,
            Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid or expired token',
                    'timestamp' => Carbon::now()->toISOString(),
                    'status_code' => 401,
                ], 401);
            }
        });



        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'timestamp' => Carbon::now()->toISOString(),
                    'status_code' => 422,
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $modelClass = $previous->getModel();
                $modelBase  = class_basename($modelClass);
                $modelSlug  = Str::upper(Str::snake($modelBase));

                return response()->json([
                    'success'     => false,
                    'code'        => $modelSlug . '_NOT_FOUND',
                    'message'     => $modelBase . ' not found',
                    'timestamp'   => Carbon::now()->toISOString(),
                    'status_code' => 404,
                ], 404);
            }

            return response()->json([
                'success'     => false,
                'code'        => 'ENDPOINT_NOT_FOUND',
                'message'     => 'Endpoint not found',
                'timestamp'   => Carbon::now()->toISOString(),
                'status_code' => 404,
            ], 404);
        });

    })->create();
