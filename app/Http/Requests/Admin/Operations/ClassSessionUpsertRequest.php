<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Enums\ClassSessionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ClassSessionUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'string', 'max:16'],
            'end_time' => ['required', 'string', 'max:16'],
            'total_spots' => ['required', 'integer', 'min:1', 'max:999'],
            'status' => ['required', Rule::enum(ClassSessionStatusEnum::class)],
        ];
    }
}
