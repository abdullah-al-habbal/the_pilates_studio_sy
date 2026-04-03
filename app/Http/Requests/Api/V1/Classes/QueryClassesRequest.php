<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Classes;

use Illuminate\Foundation\Http\FormRequest;

class QueryClassesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date_format:Y-m-d',
            'start_after' => 'nullable|date_format:H:i',
            'start_before' => 'nullable|date_format:H:i',
            'category_id' => 'nullable|exists:class_categories,id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'per_page' => 'integer|min:1|max:100',
        ];
    }
}
