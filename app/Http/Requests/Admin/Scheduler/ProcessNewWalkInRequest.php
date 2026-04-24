<?php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use Illuminate\Foundation\Http\FormRequest;

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
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}
