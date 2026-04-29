<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

class RecordExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'amount'        => ['required', 'integer', 'min:0'],
            'notes'         => ['nullable', 'string'],
            'date'          => ['nullable', 'date'],
        ];
    }
}
