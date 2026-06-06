<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use Illuminate\Foundation\Http\FormRequest;

class RejectExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense' => [
                'required',
                'integer',
                'exists:club_expenses,id',
                function ($attribute, $value, $fail) {
                    $expense = ClubExpense::find($value);
                    if ($expense && $expense->status !== ClubExpenseStatusEnum::PENDING) {
                        $fail('This expense has already been ' . $expense->status->value . '.');
                    }
                },
            ],
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
