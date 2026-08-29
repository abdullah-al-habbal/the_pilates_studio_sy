<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Repositories\Eloquent\User\UserEloquentRepository;

final readonly class CreateClientHandler
{
    public function __construct(
        private UserEloquentRepository $userRepository,
    ) {}

    public function handle(
        string $fullname,
        string $phoneNumber,
        ?string $email,
        ?string $dateOfBirth,
        string $password,
    ): User {
        return $this->userRepository->create([
            'fullname' => $fullname,
            'phone_number' => $phoneNumber,
            'email' => $email,
            'date_of_birth' => $dateOfBirth,
            'password' => $password,
            'role' => UserRoleEnum::CUSTOMER->value,
        ]);
    }
}
