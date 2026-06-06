<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final readonly class GetPendingExpensesAction
{
    use ApiResponseTrait;

    public function __invoke(): JsonResponse
    {
        if (!Auth::user()?->isMainAdmin()) {
            return $this->forbidden('Only main admin can view pending approvals.');
        }

        $expenses = ClubExpense::with(['category', 'recordedBy', 'currency'])
            ->where('status', ClubExpenseStatusEnum::PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(ClubExpense $e): array => [
                'id'               => $e->id,
                'category_name'    => $e->category?->name,
                'amount'           => $e->amount,
                'currency_code'    => $e->currency->code,
                'currency_symbol'  => $e->currency->symbol,
                'currency_decimals'=> $e->currency->decimal_places,
                'recorded_by_name' => $e->recordedBy?->fullname,
                'recorded_by_id'   => $e->recorded_by,
                'expense_date'     => $e->expense_date?->toDateString(),
                'notes'            => $e->notes,
                'created_at'       => $e->created_at?->toDateTimeString(),
            ]);

        return $this->success(data: $expenses->values()->all());
    }
}
