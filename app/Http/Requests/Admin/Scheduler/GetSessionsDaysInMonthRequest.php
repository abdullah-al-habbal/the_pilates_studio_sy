<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use Illuminate\Foundation\Http\FormRequest;

final class GetSessionsDaysInMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    public function getYear(): int
    {
        return (int) $this->input('year', now()->year);
    }

    public function getMonth(): int
    {
        return (int) $this->input('month', now()->month);
    }
}
