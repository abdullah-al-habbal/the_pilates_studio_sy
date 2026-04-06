<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\BookingSession;

use Illuminate\Foundation\Http\FormRequest;

class ListUserSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:upcoming,past,both'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function getType(): string
    {
        return $this->input('type', 'both');
    }

    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 20);
    }
}
