<?php

// filePath: app/Http/Controllers/Api/V1/Auth/EmailVerificationController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

#[Group('Authentication')]
class EmailVerificationController extends BaseApiController
{
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();

        if (! is_null($user->email_verified_at)) {
            return $this->error('Email is already verified.', 422);
        }

        if (is_null($user->otp_code) || is_null($user->otp_expires_at)) {
            return $this->error('No OTP found. Please request a new one.', 422);
        }

        if ($user->otp_expires_at->isPast()) {
            return $this->error('OTP has expired. Please request a new one.', 422);
        }

        if (! Hash::check($request->otp, $user->otp_code)) {
            return $this->error('Invalid OTP.', 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Email verified successfully.');
    }

    public function resend(ResendOtpRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = User::where('email', $request->email)->firstOrFail();

        if (! is_null($user->email_verified_at)) {
            return $this->error('Email is already verified.', 422);
        }

        $otp = (string) random_int(100000, 999999);

        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        // Dispatch: SendOtpJob::dispatch($user, $otp);

        return $this->success(
            ['email' => $user->email],
            'OTP resent. Please check your email.',
        );
    }
}
