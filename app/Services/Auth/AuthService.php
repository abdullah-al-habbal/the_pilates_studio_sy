<?php

// filePath: app/Services/Auth/AuthService.php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Events\UserSuccessfullyRegisteredEvent;
use App\Jobs\Auth\SendOtpJob;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function register(array $data): User
    {
        $user = $this->userService->createUser($data);
        $this->sendOtp($user);

        event(new UserSuccessfullyRegisteredEvent($user));

        return $user;
    }

    public function attemptLogin(string $email, string $password): ?User
    {
        $user = $this->userService->findByEmailWithTrashed($email);

        if (! $user || ! $this->userService->validateCredentials($user, $password)) {
            return null;
        }
        if ($user->trashed()) {
            return null;
        }

        return $user;
    }

    public function reactivateAccount(string $email, string $password): ?User
    {
        $user = $this->userService->findByEmailWithTrashed($email);
        if (! $user || ! $user->trashed() || ! $this->userService->validateCredentials($user, $password)) {
            return null;
        }
        $this->userService->reactivateUser($user);
        $this->sendOtp($user);

        return $user;
    }

    public function createToken(User $user, ?string $deviceName = null): string
    {
        $tokenName = $deviceName ?? 'auth_token_'.Carbon::now()->timestamp;

        return $user->createToken($tokenName)->plainTextToken;
    }

    public function sendOtp(User $user): string
    {
        $otp = $this->userService->generateOtp();
        $this->userService->saveOtp($user, $otp);

        SendOtpJob::dispatch($user, $otp);

        if (app()->environment('local') || config('app.debug') || config('auth.return_otp_in_response')) {
            Log::info('OTP for user '.$user->email.': '.$otp);
            if (config('auth.return_otp_in_response')) {
                cache()->put("test_otp_{$user->email}", $otp, now()->addMinutes(15));
            }
        }

        return $otp;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
