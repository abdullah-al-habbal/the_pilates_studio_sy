<?php

// filePath: app/Http/Controllers/Api/V1/Auth/AuthController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Auth\AuthService;
use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
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

    #[Endpoint('Register', description: 'Register a new user.')]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return $this->created(
            ['email' => $user->email],
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
            $this->authService->sendOtp($user);

            return $this->error(
                ErrorCodeEnum::EMAIL_NOT_VERIFIED,
                'Email not verified. A new OTP has been sent to your email.',
                ErrorCodeEnum::EMAIL_NOT_VERIFIED->getStatusCode(),
                ['email_verified' => false],
            );
        }

        $deviceName = $data['device_name'] ?? null;
        $token = $this->authService->createToken($user, $deviceName);

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
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
        return $this->success(
            new UserResource($request->user()),
            SuccessCodeEnum::SUCCESS,
            SuccessCodeEnum::SUCCESS->getMessage()
        );
    }
}
