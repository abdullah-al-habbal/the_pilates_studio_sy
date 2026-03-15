<?php

// filePath: app/Services/User/UserService.php

declare(strict_types=1);

namespace App\Services\User;

use App\Events\User\UserRegisteredEvent;
use App\Models\User;
use App\Repositories\Eloquent\User\UserEloquentRepository;
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
        return (string) random_int(100000, 999999);
    }
}
