<?php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use Illuminate\Foundation\Http\FormRequest;

final class GetDailySessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function getDate(): string
    {
        return $this->input('date', today()->format('Y-m-d'));
    }

    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 10);
    }
}
