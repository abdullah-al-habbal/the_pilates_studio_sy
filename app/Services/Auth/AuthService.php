<?php
// filePath: app/Services/Auth/AuthService.php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Jobs\Auth\SendOtpJob;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Support\Carbon;

class AuthService
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function register(array $data): User
    {
        $user = $this->userService->createUser($data);
        $this->sendOtp($user);

        return $user;
    }

    public function attemptLogin(string $email, string $password): ?User
    {
        $user = $this->userService->findByEmail($email);

        if (! $user || ! $this->userService->validateCredentials($user, $password)) {
            return null;
        }

        return $user;
    }

    public function createToken(User $user, ?string $deviceName = null): string
    {
        $tokenName = $deviceName ?? 'auth_token_' . Carbon::now()->timestamp;

        return $user->createToken($tokenName)->plainTextToken;
    }

    public function sendOtp(User $user): void
    {
        $otp = $this->userService->generateOtp();
        $this->userService->saveOtp($user, $otp);

        SendOtpJob::dispatch($user, $otp);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
