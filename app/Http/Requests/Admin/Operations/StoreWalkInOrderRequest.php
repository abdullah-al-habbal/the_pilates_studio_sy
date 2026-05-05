<?php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWalkInOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchandise_id' => ['required', 'exists:center_merchandises,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'fullname' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone_number')->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => 'This phone number is already registered. Use the existing client dropdown instead.',
            'email.unique' => 'This email address is already registered to an active account.',
        ];
    }
}
