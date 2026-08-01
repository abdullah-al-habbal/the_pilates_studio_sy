<?php

declare(strict_types=1);

namespace App\DTOs\Operations;

final readonly class PlaceOrderDTO
{
    public function __construct(
        public int $customerId,
        public int $merchandiseId,
        public int $quantity,
        public int $currencyId,
        public ?int $createdBy = null,
    ) {}
}
