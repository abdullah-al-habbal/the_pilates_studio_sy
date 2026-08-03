<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'exists:packages,id'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'paid_amount' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
