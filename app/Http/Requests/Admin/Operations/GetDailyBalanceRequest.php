<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

class GetDailyBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'currencies' => ['nullable', 'array'],
            'currencies.*' => ['string', 'max:10'],
            'convertToBase' => ['nullable', 'boolean'],
        ];
    }

    public function date(): string
    {
        return $this->validated('date') ?? now()->toDateString();
    }

    /** @return list<string>|null */
    public function currencyCodes(): ?array
    {
        $currencies = $this->validated('currencies');

        return is_array($currencies) && $currencies !== [] ? $currencies : null;
    }

    public function convertToBase(): bool
    {
        return $this->boolean('convertToBase');
    }
}
