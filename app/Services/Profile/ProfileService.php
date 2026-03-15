<?php
// filePath: app/Services/Profile/ProfileService.php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\User;
use App\Repositories\Eloquent\User\UserEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfileService
{
    public function __construct(
        private readonly UserEloquentRepository $userRepository
    ) {}

    public function getProfileWithSettings(int $userId): User
    {
        $user = $this->userRepository->findWithSettings($userId);

        if (! $user) {
            throw new ModelNotFoundException("User with ID {$userId} not found.");
        }

        return $user;
    }

    public function updateProfile(int $userId, array $data): User
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new ModelNotFoundException("User with ID {$userId} not found.");
        }

        $this->userRepository->update($user, $data);

        return $user->fresh('settings.preferredLanguage');
    }

    public function deleteAccount(int $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new ModelNotFoundException("User with ID {$userId} not found.");
        }

        $this->userRepository->deleteAccount($user);
    }
}
