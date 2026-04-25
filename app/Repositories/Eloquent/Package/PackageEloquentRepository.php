<?php

// filePath: app/Repositories/Eloquent/Package/PackageEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Package;

use App\Models\Package;

class PackageEloquentRepository
{
    public function getCheapestActivePackage(): ?Package
    {
        return Package::where('is_active', true)
            ->orderBy('total_credits', 'asc')
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
        return Package::create([
            'name' => ['en' => 'Walk-in Session', 'ar' => 'جلسة مباشرة'],
            'total_credits' => 1,
            'price' => 0,
            'is_active' => true,
        ]);
    }
}
