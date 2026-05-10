<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin\Operations;
use Illuminate\Foundation\Http\FormRequest;
class CreatePackageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'total_credits' => ['required', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:0'],
            'currency_id'   => ['required', 'integer', 'exists:currencies,id'],
            'amount'        => ['required', 'integer', 'min:0'],
        ];
    }
}
