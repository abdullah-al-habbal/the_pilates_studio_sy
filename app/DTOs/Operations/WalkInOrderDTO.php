<?php

declare(strict_types=1);

namespace App\DTOs\Operations;

final readonly class WalkInOrderDTO
{
    public function __construct(
        public int $merchandiseId,
        public int $quantity,
        public int $currencyId,
        public string $fullname,
        public string $phoneNumber,
        public ?string $email,
        public ?int $createdBy = null,
    ) {}
}
