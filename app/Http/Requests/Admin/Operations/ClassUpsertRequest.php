<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Enums\ClassStatusEnum;
use App\Enums\WeekdayEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'array'],
            'about.en' => ['nullable', 'string'],
            'about.ar' => ['nullable', 'string'],
            'instructor_id' => ['nullable', 'integer', Rule::exists('instructors', 'id')->whereNull('deleted_at')],
            'class_category_id' => ['required', 'integer', Rule::exists('class_categories', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::enum(ClassStatusEnum::class)],
            'schedule_mode' => ['required', Rule::in(['weekdays', 'interval'])],
            'weekdays' => ['nullable', 'array', 'min:1', 'required_if:schedule_mode,weekdays'],
            'weekdays.*' => [Rule::enum(WeekdayEnum::class)],
            'recurrence_pattern_id' => ['nullable', 'integer', 'required_if:schedule_mode,interval', Rule::exists('recurrence_patterns', 'id')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['required', 'string', 'max:16'],
            'end_time' => ['required', 'string', 'max:16'],
            'total_spots' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
