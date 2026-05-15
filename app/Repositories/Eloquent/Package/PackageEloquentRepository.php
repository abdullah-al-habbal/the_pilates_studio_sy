<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Package;

use App\Models\Package;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;

class PackageEloquentRepository
{
    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly PricingService $pricingService,
        private readonly Package $model
    ) {
    }

    public function getCheapestActivePackage(): ?Package
    {
        $baseCurrencyId = $this->pricingService->getBaseCurrencyId();

        return Package::where('is_active', true)
            ->whereHas('prices', fn($q) => $q->where('currency_id', $baseCurrencyId))
            ->with(['prices' => fn($q) => $q->where('currency_id', $baseCurrencyId)])
            ->get()
            ->sortBy(fn($p) => $p->prices->first()?->amount ?? PHP_INT_MAX)
            ->first();
    }

    public function findUserBookedPackage(int $userId, int $packageId): ?Package
    {
        return Package::whereHas('bookings', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->find($packageId);
    }

    public function findById(int $id): ?Package
    {
        return Package::find($id);
    }

    public function hasActivePackage(): bool
    {
        return Package::where('is_active', true)->exists();
    }

    public function findActiveWalkInPackage(): ?Package
    {
        return Package::where('total_credits', 1)
            ->where('is_active', true)
            ->first();
    }

    public function createWalkInPackage(): Package
    {
        $baseCurrencyId = $this->pricingService->getBaseCurrencyId();

        $package = Package::create([
            'name' => ['en' => 'Walk-in Session', 'ar' => 'جلسة دخول مباشر'],
            'total_credits' => 1,
            'is_active' => true,
        ]);

        // fix: the walk out not free. it based on the same package price. so, we must update the method to accept the currecny and price
        $package->prices()->create([
            'currency_id' => $baseCurrencyId,
            'amount' => 0,
        ]);

        return $package;
    }
}
