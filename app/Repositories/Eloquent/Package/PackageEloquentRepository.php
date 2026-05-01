<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Package;

use App\Models\Package;
use App\Services\Currency\CurrencyService;

class PackageEloquentRepository
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {}

    public function getCheapestActivePackage(): ?Package
    {
        $currencyId = $this->currencyService->getDefaultCurrency()->id;
        return Package::where('is_active', true)
            ->whereHas('prices', fn($q) => $q->where('currency_id', $currencyId))
            ->with(['prices' => fn($q) => $q->where('currency_id', $currencyId)])
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
        $package = Package::create([
            'name' => ['en' => 'Walk-in Session', 'ar' => 'جلسة دخول مباشر'],
            'total_credits' => 1,
            'is_active' => true,
        ]);
        $this->currencyService->getAllActiveCurrencies()->each(function ($currency) use ($package) {
            $package->prices()->create([
                'currency_id' => $currency->id,
                'amount' => 0,
            ]);
        });
        return $package;
    }
}
