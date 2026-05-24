<?php

declare(strict_types=1);

namespace App\Commands\Admin\Operations;

use App\Http\Requests\Admin\Operations\RecordExpenseRequest;
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

    public static function fromRequest(RecordExpenseRequest $request, int $recordedBy): self
    {
        return new self(
            categoryName: $request->category_name,
            currencyId: (int) $request->currency_id,
            amount: (int) $request->amount,
            recordedBy: $recordedBy,
            notes: $request->notes,
            expenseDate: $request->date ? Carbon::parse($request->date) : null,
        );
    }
}
