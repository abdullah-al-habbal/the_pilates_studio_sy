<?php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use App\Commands\Admin\Scheduler\ProcessNewWalkInCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProcessNewWalkInRequest extends FormRequest
{
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

            'password' => ['nullable', 'string', 'min:6'],
        ];
    }

    public function toCommand(int $sessionId): ProcessNewWalkInCommand
    {
        return new ProcessNewWalkInCommand(
            sessionId: $sessionId,
            fullname: $this->validated('fullname'),
            phoneNumber: $this->validated('phone_number'),
            email: $this->validated('email'),
            password: $this->validated('password') ?? 'pilates',
        );
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => 'This phone number is already registered to an active account.',
            'email.unique' => 'This email address is already registered to an active account.',
        ];
    }
}
