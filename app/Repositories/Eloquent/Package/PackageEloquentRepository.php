<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Package;

use App\Enums\PackageTypeEnum;
use App\Models\Package;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;
use Illuminate\Database\Eloquent\Collection;

class PackageEloquentRepository
{
    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly PricingService $pricingService,
        private readonly Package $model
    ) {}

    public function getTopActivePackages(int $limit = 3): Collection
    {
        $baseCurrencyId = $this->pricingService->getBaseCurrencyId();

        return Package::where('is_active', true)
            ->whereHas('prices', fn ($q) => $q->where('currency_id', $baseCurrencyId))
            ->with(['prices' => fn ($q) => $q->where('currency_id', $baseCurrencyId)])
            ->get()
            ->sortBy(fn ($p) => $p->prices->first()?->amount ?? PHP_INT_MAX)
            ->take($limit);
    }

    public function getCheapestActivePackage(): ?Package
    {
        $baseCurrencyId = $this->pricingService->getBaseCurrencyId();

        return Package::where('is_active', true)
            ->whereHas('prices', fn ($q) => $q->where('currency_id', $baseCurrencyId))
            ->with(['prices' => fn ($q) => $q->where('currency_id', $baseCurrencyId)])
            ->get()
            ->sortBy(fn ($p) => $p->prices->first()?->amount ?? PHP_INT_MAX)
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
        return Package::where('type', PackageTypeEnum::WALK_IN)
            ->where('is_active', true)
            ->first();
    }

    public function findOrCreateWalkInPackage(int $price): Package
    {
        $package = $this->findActiveWalkInPackage();

        if (! $package) {
            $baseCurrencyId = $this->pricingService->getBaseCurrencyId();

            $package = Package::create([
                'name' => ['en' => 'Walk-in Session', 'ar' => 'جلسة دخول مباشر'],
                'total_credits' => 1,
                'is_active' => true,
                'type' => PackageTypeEnum::WALK_IN,
            ]);

            $package->prices()->create([
                'currency_id' => $baseCurrencyId,
                'amount' => $price,
            ]);

            return $package;
        }

        $baseCurrencyId = $this->pricingService->getBaseCurrencyId();
        $priceRow = $package->prices()->where('currency_id', $baseCurrencyId)->first();

        if ($priceRow) {
            if ((int) $priceRow->amount !== $price) {
                $priceRow->update(['amount' => $price]);
            }
        } else {
            $package->prices()->create([
                'currency_id' => $baseCurrencyId,
                'amount' => $price,
            ]);
        }

        return $package;
    }
}
