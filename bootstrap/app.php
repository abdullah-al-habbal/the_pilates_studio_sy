<?php

// filePath: bootstrap/app.php

declare(strict_types=1);

use App\Http\Middleware\EnsureActiveBookingMiddleware;
use App\Http\Middleware\EnsureActivePackageMiddleware;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\FreezeUserMiddleware;
use App\Http\Middleware\MobileAppVersion\CheckAppVersionMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web/web.php',
        api: __DIR__ . '/../routes/api/api.php',
        commands: __DIR__ . '/../routes/console/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/admin/login');

        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.sanctum' => EnsureFrontendRequestsAreStateful::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'ensure.active.booking' => EnsureActiveBookingMiddleware::class,
            'ensure.active.package' => EnsureActivePackageMiddleware::class,
            'freeze.user' => FreezeUserMiddleware::class,
            'role.admin' => EnsureUserIsAdmin::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'precognitive' => HandlePrecognitiveRequests::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);

        $middleware->web(append: [
            SetLocaleMiddleware::class,
        ]);

        $middleware->api(prepend: [
            FreezeUserMiddleware::class,
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            CheckAppVersionMiddleware::class,
            EnsureActivePackageMiddleware::class,
            SubstituteBindings::class,
        ]);

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
            if (!$request->expectsJson()) {
                return null;
            }

            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $modelClass = $previous->getModel();
                $modelBase = class_basename($modelClass);
                $modelSlug = Str::upper(Str::snake($modelBase));

                return response()->json([
                    'success' => false,
                    'code' => $modelSlug . '_NOT_FOUND',
                    'message' => $modelBase . ' not found',
                    'timestamp' => Carbon::now()->toISOString(),
                    'status_code' => 404,
                ], 404);
            }

            return response()->json([
                'success' => false,
                'code' => 'ENDPOINT_NOT_FOUND',
                'message' => 'Endpoint not found',
                'timestamp' => Carbon::now()->toISOString(),
                'status_code' => 404,
            ], 404);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            Log::error('Unhandled API Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'code' => 'INTERNAL_SERVER_ERROR',
                'message' => app()->environment('production')
                    ? 'An internal server error occurred. Please try again later.'
                    : $e->getMessage(),
                'timestamp' => Carbon::now()->toISOString(),
                'status_code' => 500,
            ], 500);
        });

    });

return $app->create();