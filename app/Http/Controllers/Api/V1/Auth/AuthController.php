<?php

// filePath: app/Http/Controllers/Api/V1/Auth/AuthController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\RichUserResource;
use App\Services\Auth\AuthService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('authentication')]
class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        $user->load('activeCreditBooking.package');

        return $this->created(
            new RichUserResource($user),
            SuccessCodeEnum::REGISTER_SUCCESS,
            SuccessCodeEnum::REGISTER_SUCCESS->getMessage(),
        );
    }

    #[Endpoint('Login', description: 'Authenticate a user and return a token.')]
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->attemptLogin($data['email'], $data['password']);

        if (! $user) {
            return $this->error(
                ErrorCodeEnum::INVALID_CREDENTIALS,
                ErrorCodeEnum::INVALID_CREDENTIALS->getMessage(),
                ErrorCodeEnum::INVALID_CREDENTIALS->getStatusCode()
            );
        }

        if (! $user->isActive()) {
            return $this->error(
                ErrorCodeEnum::ACCOUNT_LOCKED,
                'Your account has been deactivated.',
                ErrorCodeEnum::ACCOUNT_LOCKED->getStatusCode()
            );
        }

        if (is_null($user->email_verified_at)) {
            $otp = $this->authService->sendOtp($user);

            $extra = ['email_verified' => false];
            if (app()->environment('local') || config('app.debug') || config('auth.return_otp_in_response')) {
                $extra['otp'] = $otp;
            }

            return $this->error(
                ErrorCodeEnum::EMAIL_NOT_VERIFIED,
                'Email not verified. A new OTP has been sent to your email.',
                ErrorCodeEnum::EMAIL_NOT_VERIFIED->getStatusCode(),
                $extra,
            );
        }

        $deviceName = $data['device_name'] ?? null;
        $token = $this->authService->createToken($user, $deviceName);

        $user->load('activeCreditBooking.package');

        return $this->success([
            'token' => $token,
            'user' => new RichUserResource($user),
        ], SuccessCodeEnum::LOGIN_SUCCESS, SuccessCodeEnum::LOGIN_SUCCESS->getMessage());
    }

    #[Endpoint('Logout', description: 'Logout the authenticated user.')]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->noContent(SuccessCodeEnum::LOGOUT_SUCCESS, SuccessCodeEnum::LOGOUT_SUCCESS->getMessage());
    }

    #[Endpoint('Me', description: 'Get the authenticated user profile.')]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('activeCreditBooking.package');

        return $this->success(
            new RichUserResource($user),
            SuccessCodeEnum::SUCCESS,
            SuccessCodeEnum::SUCCESS->getMessage()
        );
    }
}
