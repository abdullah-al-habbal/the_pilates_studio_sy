<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Models\Package;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;

class LandingPackageVO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $credits,
        public readonly int $validityDays,
        public readonly int $price,
        public readonly string $currency,
        public readonly array $features,
        public readonly bool $recommended,
    ) {}

    public static function fromModel(Package $package): self
    {
        $pricing = app(PricingService::class);
        $currencyService = app(CurrencyService::class);
        $basePrice = $pricing->getBasePrice($package) ?? 0;
        $currencyCode = $currencyService->getBaseCurrency()->code;
        return new self(
            id: $package->id,
            name: $package->getTranslation('name', app()->getLocale()),
            credits: $package->total_credits,
            validityDays: $package->validity_days ?? 30,
            price: (int) $basePrice,
            currency: $currencyCode,
            features: $package->features ?? [],
            recommended: false,
        );
    }
}
