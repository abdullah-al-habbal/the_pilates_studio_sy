<?php

// filePath: app/Repositories/Eloquent/User/UserEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\User;

use App\Events\User\UserRegisteredEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserEloquentRepository
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            UserRegisteredEvent::dispatch($user);

            return $user;
        });
    }

    public function createInTransaction(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return $this->create($data);
        });
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function updateOtp(User $user, string $hashedOtp): bool
    {
        return (bool) $user->update([
            'otp_code' => $hashedOtp,
            'otp_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function markEmailAsVerified(User $user): bool
    {
        return (bool) $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
    }
}
