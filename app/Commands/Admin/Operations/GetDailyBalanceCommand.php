<?php

declare(strict_types=1);

namespace App\Commands\Admin\Operations;

final readonly class GetDailyBalanceCommand
{
    public function __construct(
        public string $date,
        /** @var list<string>|null */
        public ?array $currencyCodes,
        public bool $convertToBase,
    ) {
    }
}
