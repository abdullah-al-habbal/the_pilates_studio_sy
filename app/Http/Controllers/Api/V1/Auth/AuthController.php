<?php

// filePath: app/Http/Controllers/Api/V1/Auth/AuthController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Auth\AuthService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Authentication')]
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
            'Registration successful. Please verify your email.',
        );
    }

    #[Endpoint('Login', description: 'Authenticate a user and return a token.')]
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->attemptLogin($data['email'], $data['password']);

        if (! $user) {
            return $this->error('Invalid credentials.', 401);
        }

        if (! $user->isActive()) {
            return $this->error('Your account has been deactivated.', 403);
        }

        if (is_null($user->email_verified_at)) {
            $this->authService->sendOtp($user);

            return $this->error(
                'Email not verified. A new OTP has been sent to your email.',
                403,
                ['email_verified' => false],
            );
        }

        $deviceName = $data['device_name'] ?? null;
        $token = $this->authService->createToken($user, $deviceName);

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Login successful.');
    }

    #[Endpoint('Logout', description: 'Logout the authenticated user.')]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->noContent('Logged out successfully.');
    }

    #[Endpoint('Me', description: 'Get the authenticated user profile.')]
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }
}
