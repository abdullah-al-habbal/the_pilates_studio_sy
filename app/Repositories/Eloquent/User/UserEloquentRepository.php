<?php

// filePath: app/Repositories/Eloquent/User/UserEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\User;

use App\Enums\BookingStatusEnum;
use App\Events\User\UserRegisteredEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
class UserEloquentRepository
{
    public function countActiveUsers(): int
    {
        return User::whereHas('bookings', function ($q) {
            $q->where('status', BookingStatusEnum::ACTIVE)
                ->where(function ($sq) {
                    $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
        })->count();
    }

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

    public function findWithSettings(int $id): ?User
    {
        return User::with('settings.preferredLanguage')->find($id);
    }

    public function update(User $user, array $data): bool
    {
        return (bool) $user->update($data);
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

    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->update(['deleted_by' => $user->id]);
            $user->delete();
        });
    }

    public function findByEmailWithTrashed(string $email): ?User
    {
        return User::withTrashed()->where('email', $email)->first();
    }

    public function findByPhoneWithTrashed(string $phoneNumber): ?User
    {
        return User::withTrashed()->where('phone_number', $phoneNumber)->first();
    }

    public function findByIdWithTrashed(int $id): ?User
    {
        return User::withTrashed()->find($id);
    }

    public function forceDeleteAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->forceDelete();
        });
    }

    public function list(array $filters = []): EloquentCollection
    {
        return User::query()
            ->orderBy('fullname')
            ->get();
    }

    public function existsByFieldWithoutDeleted(string $field, mixed $value): bool
    {
        return User::whereNull('deleted_at')
            ->where($field, $value)
            ->exists();
    }

    public function listExcludingSession(int $sessionId): Collection
    {
        return User::orderBy('fullname')
            ->whereDoesntHave('bookingSessions', function ($q) use ($sessionId) {
                $q->where('class_session_id', $sessionId);
            })
            ->get(['id', 'fullname', 'phone_number']);
    }
}
