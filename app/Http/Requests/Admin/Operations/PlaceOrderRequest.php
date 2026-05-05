<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'    => ['required', 'exists:users,id'],
            'merchandise_id' => ['required', 'exists:center_merchandises,id'],
            'quantity'       => ['required', 'integer', 'min:1'],
            'currency_id'    => ['required', 'exists:currencies,id'],
        ];
    }
}
