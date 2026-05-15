<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

class AssignPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'paid_amount' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
