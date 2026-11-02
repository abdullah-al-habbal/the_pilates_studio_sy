<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRoleEnum::CUSTOMER->value)
                    ->whereNull('deleted_at')
                    ->whereNull('deactivated_at')
                    ->whereNot('status', UserStatusEnum::DEACTIVATED->value),
            ],
            'merchandise_id' => [
                'required',
                'integer',
                Rule::exists('center_merchandises', 'id')->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            // Optional back-dating for data entry; defaults to the server time. Never in the future.
            'ordered_at' => ['nullable', 'date', 'after:2000-01-01', 'before_or_equal:now'],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => __('dashboard.operations_ui.store.customer_not_eligible'),
            'ordered_at.before_or_equal' => __('dashboard.operations_ui.store.purchase_date_future'),
            'currency_id.exists' => __('dashboard.operations_ui.store.currency_not_available'),
        ];
    }
}
