<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'purchased_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
        ];
    }
}
