<?php

// filePath: app/Services/User/UserService.php

declare(strict_types=1);

namespace App\Services\User;

use App\Events\User\UserRegisteredEvent;
use App\Models\User;
use App\Repositories\Eloquent\User\UserEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserEloquentRepository $userRepository
    ) {}

    public function createUser(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'password' => $data['password'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ]);

            UserRegisteredEvent::dispatch($user);

            return $user;
        });

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function validateCredentials(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function isAccountActive(User $user): bool
    {
        return $user->isActive();
    }

    public function isEmailVerified(User $user): bool
    {
        return ! is_null($user->email_verified_at);
    }

    public function saveOtp(User $user, string $otp): void
    {
        $this->userRepository->updateOtp($user, Hash::make($otp));
    }

    public function verifyEmail(User $user): void
    {
        $this->userRepository->markEmailAsVerified($user);
    }

    public function generateOtp(): string
    {
        return (string) random_int(1000, 9999);
    }

    public function findByEmailOrFail(string $email): User
    {
        $user = $this->findByEmail($email);
        if (! $user) {
            throw new ModelNotFoundException;
        }

        return $user;
    }

    public function hasValidOtp(User $user): bool
    {
        return ! is_null($user->otp_code) && ! is_null($user->otp_expires_at);
    }

    public function isOtpExpired(User $user): bool
    {
        return $user->otp_expires_at && $user->otp_expires_at->isPast();
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        return Hash::check($otp, $user->otp_code);
    }

    public function findByEmailWithTrashed(string $email): ?User
    {
        return $this->userRepository->findByEmailWithTrashed($email);
    }

    public function reactivateUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update([
                'deactivated_at' => null,
                'deleted_by' => null,
                'email_verified_at' => null,
            ]);
        });
    }

    public function countActiveUsers(): int
    {
        return $this->userRepository->countActiveUsers();
    }
}
