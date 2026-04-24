<?php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Scheduler;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:attended,missed'],
        ];
    }
}
