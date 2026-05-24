<?php
declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

use App\Commands\Admin\Operations\RecordExpenseCommand;
use Illuminate\Support\Facades\Auth;

final class RecordExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'date' => ['nullable', 'date'],
        ];
    }

    public function toCommand(): RecordExpenseCommand
    {
        return RecordExpenseCommand::fromRequest(
            request: $this,
            recordedBy: (int) Auth::id(),
        );
    }
}
