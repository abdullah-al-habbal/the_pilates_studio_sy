<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ValidateWalkInFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field' => ['required', 'string', Rule::in(['phone_number', 'email'])],
            'value' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'field.in' => 'Invalid field. Allowed: phone_number, email.',
        ];
    }
}
