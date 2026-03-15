<?php

// filePath: app/Http/Requests/Api/V1/Notification/BulkMarkAsReadRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use Illuminate\Foundation\Http\FormRequest;

class BulkMarkAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:app_notifications,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Notification IDs are required.',
            'ids.array' => 'IDs must be an array.',
            'ids.min' => 'At least one notification ID is required.',
            'ids.max' => 'Maximum 100 notifications can be marked as read at once.',
            'ids.*.integer' => 'Each ID must be an integer.',
        ];
    }
}
