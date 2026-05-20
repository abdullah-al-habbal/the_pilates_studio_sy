<?php

declare(strict_types=1);

namespace App\Commands\Admin\Operations;

use Carbon\Carbon;

final readonly class RecordExpenseCommand
{
    public function __construct(
        public string $categoryName,
        public int $currencyId,
        public int $amount,
        public int $recordedBy,
        public ?string $notes = null,
        public ?Carbon $expenseDate = null,
    ) {
    }
}
