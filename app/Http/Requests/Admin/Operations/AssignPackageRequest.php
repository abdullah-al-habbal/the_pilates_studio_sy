<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use App\Models\Currency;
use App\Models\Package;
use App\Models\Price;
use Illuminate\Foundation\Http\FormRequest;

class AssignPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'paid_amount' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $packageId = $this->route('packageId');
                    $currencyId = (int) $this->input('currency_id');

                    $package = Package::find($packageId);
                    if (!$package) {
                        return $fail('Package not found.');
                    }

                    $priceAmount = Price::where('priceable_type', Package::class)
                        ->where('priceable_id', $packageId)
                        ->where('currency_id', $currencyId)
                        ->value('amount');

                    if ($priceAmount === null) {
                        return $fail('This package has no price configured for the selected currency.');
                    }

                    if ((int) $value !== $priceAmount) {
                        $currency = Currency::find($currencyId);
                        $symbol = $currency ? $currency->symbol : '';
                        $formattedPrice = $priceAmount . ' ' . $symbol;
                        $fail("The paid amount must equal the package price ({$formattedPrice}).");
                    }
                },
            ],
        ];
    }
}
