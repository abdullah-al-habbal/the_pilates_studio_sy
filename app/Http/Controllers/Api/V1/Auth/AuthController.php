<?php

// filePath: app/Http/Controllers/Api/V1/Auth/AuthController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'fullname'      => $request->fullname,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'password'      => $request->password,
            'date_of_birth' => $request->date_of_birth,
        ]);

        $this->sendOtp($user);

        return $this->created(
            ['email' => $user->email],
            'Registration successful. Please verify your email.',
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if (! $user->isActive()) {
            return $this->error('Your account has been deactivated.', 403);
        }

        if (is_null($user->email_verified_at)) {
            $this->sendOtp($user);

            return $this->error(
                'Email not verified. A new OTP has been sent to your email.',
                403,
                ['email_verified' => false],
            );
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => new UserResource($user),
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent('Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    private function sendOtp(User $user): void
    {
        $otp = (string) random_int(100000, 999999);

        $user->update([
            'otp_code'       => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        // Dispatch: SendOtpJob::dispatch($user, $otp);
    }
}
