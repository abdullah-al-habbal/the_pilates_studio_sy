<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateClientRequest extends FormRequest
{
    public const DEFAULT_PASSWORD = 'pilates';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => __('dashboard.operations_ui.clients.error_phone_taken'),
            'email.unique' => __('dashboard.operations_ui.clients.error_email_taken'),
        ];
    }

    public function password(): string
    {
        return $this->filled('password')
            ? $this->string('password')->toString()
            : self::DEFAULT_PASSWORD;
    }
}
