<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class BatchUpdateBookingSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.booking_session_id' => ['nullable', 'integer', 'required_without:items.*.class_session_id'],
            'items.*.class_session_id' => ['nullable', 'integer', 'exists:class_sessions,id', 'required_without:items.*.booking_session_id'],
            'items.*.new_class_session_id' => ['nullable', 'integer', 'exists:class_sessions,id', 'required_if:items.*.status,swap'],
            'items.*.status' => ['required', 'string', 'in:attended,missed,cancelled,late_cancelled,swap,reserved'],
            'items.*.override' => ['sometimes', 'boolean'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
