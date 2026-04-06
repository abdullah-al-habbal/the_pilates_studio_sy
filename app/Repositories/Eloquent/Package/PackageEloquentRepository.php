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
}
