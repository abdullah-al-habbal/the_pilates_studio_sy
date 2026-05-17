<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

class ProcessBookingRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount') && $this->amount === '') {
            $this->merge(['amount' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
