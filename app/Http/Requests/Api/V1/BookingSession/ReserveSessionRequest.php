<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\BookingSession;

use Illuminate\Foundation\Http\FormRequest;

class ReserveSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_session_id' => [
                'required',
                'exists:class_sessions,id',
                'bail',
            ],
        ];
    }
}
