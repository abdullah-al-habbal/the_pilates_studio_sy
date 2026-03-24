<?php
// filePath: app/Http/Controllers/Api/V1/Auth/EmailVerificationController.php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Auth\AuthService;
use App\Services\User\UserService;
use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('email_verification')]
class EmailVerificationController extends BaseApiController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AuthService $authService,
    ) {}

    #[Endpoint('Verify Email OTP', description: 'Verify the OTP sent to the user email.')]
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->userService->findByEmailOrFail($request->email);

        if ($this->userService->isEmailVerified($user)) {
            return $this->error(
                ErrorCodeEnum::EMAIL_ALREADY_VERIFIED,
                ErrorCodeEnum::EMAIL_ALREADY_VERIFIED->getMessage(),
                ErrorCodeEnum::EMAIL_ALREADY_VERIFIED->getStatusCode()
            );
        }

        if (! $this->userService->hasValidOtp($user)) {
            return $this->error(
                ErrorCodeEnum::INVALID_VERIFICATION_CODE,
                'No OTP found. Please request a new one.',
                ErrorCodeEnum::INVALID_VERIFICATION_CODE->getStatusCode()
            );
        }

        if ($this->userService->isOtpExpired($user)) {
            return $this->error(
                ErrorCodeEnum::INVALID_VERIFICATION_CODE,
                'OTP has expired. Please request a new one.',
                ErrorCodeEnum::INVALID_VERIFICATION_CODE->getStatusCode()
            );
        }

        if (! $this->userService->verifyOtp($user, $request->otp)) {
            return $this->error(
                ErrorCodeEnum::INVALID_VERIFICATION_CODE,
                'Invalid OTP.',
                ErrorCodeEnum::INVALID_VERIFICATION_CODE->getStatusCode()
            );
        }

        $this->userService->verifyEmail($user);

        $token = $this->authService->createToken($user, 'mobile');

        return $this->success([
            'token' => $token,
            'user'  => new UserResource($user),
        ], SuccessCodeEnum::EMAIL_VERIFIED, SuccessCodeEnum::EMAIL_VERIFIED->getMessage());
    }

    #[Endpoint('Resend Email OTP', description: 'Resend a new OTP to the user email.')]
    public function resend(ResendOtpRequest $request): JsonResponse
    {
        $user = $this->userService->findByEmailOrFail($request->email);

        if ($this->userService->isEmailVerified($user)) {
            return $this->error(
                ErrorCodeEnum::EMAIL_ALREADY_VERIFIED,
                ErrorCodeEnum::EMAIL_ALREADY_VERIFIED->getMessage(),
                ErrorCodeEnum::EMAIL_ALREADY_VERIFIED->getStatusCode()
            );
        }

        $this->authService->sendOtp($user);

        return $this->success(
            ['email' => $user->email],
            SuccessCodeEnum::VERIFICATION_EMAIL_SENT,
            SuccessCodeEnum::VERIFICATION_EMAIL_SENT->getMessage(),
        );
    }
}
