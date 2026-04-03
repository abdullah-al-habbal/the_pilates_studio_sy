<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\ClassSession;

use Illuminate\Foundation\Http\FormRequest;

class QueryClassSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date_format:Y-m-d',
            'date_after' => 'nullable|date_format:Y-m-d',
            'date_before' => 'nullable|date_format:Y-m-d',
            'start_after' => 'nullable|date_format:H:i',
            'class_id' => 'nullable|exists:classes,id',
            'per_page' => 'integer|min:1|max:100',
        ];
    }
}
