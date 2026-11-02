<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class CancelBookingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'override' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
