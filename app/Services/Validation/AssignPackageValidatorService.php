<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Models\Currency;
use App\Models\Package;
use App\Services\Currency\PricingService;
use Illuminate\Validation\ValidationException;

final readonly class AssignPackageValidatorService
{
    public function __construct(
        private PricingService $pricingService
    ) {
    }

    public function validateAndComputeAmount(
        int $packageId,
        int $currencyId,
        ?int $clientSentAmount = null
    ): int {
        $package = Package::find($packageId);

        if (!$package) {
            throw ValidationException::withMessages([
                'package_id' => 'Package not found.',
            ]);
        }

        $basePrice = $this->pricingService->getBasePrice($package);

        if ($basePrice === null) {
            throw ValidationException::withMessages([
                'package_id' => 'This package has no base price configured.',
            ]);
        }

        $computedAmount = $this->pricingService->calculateAmount($basePrice, $currencyId);

        if ($clientSentAmount !== null && $clientSentAmount !== $computedAmount) {
            $currency = Currency::findOrFail($currencyId);
            $symbol = $currency->symbol;
            $formatted = number_format($computedAmount, $currency->decimal_places) . ' ' . $symbol;

            throw ValidationException::withMessages([
                'paid_amount' => "The paid amount must equal the package price ({$formatted}).",
            ]);
        }

        return $computedAmount;
    }
}
