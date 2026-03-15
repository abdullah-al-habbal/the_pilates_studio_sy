<?php
// filePath: app/Services/Package/PackageService.php

declare(strict_types=1);

namespace App\Services\Package;

use App\Models\Package;
use App\Repositories\Eloquent\Package\PackageEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PackageService
{
    public function __construct(
        private readonly PackageEloquentRepository $repository
    ) {}

    public function getUserBookedPackage(int $userId, int $packageId): Package
    {
        $package = $this->repository->findUserBookedPackage($userId, $packageId);

        if (! $package) {
            throw new ModelNotFoundException("Package with ID {$packageId} not found or not booked by user.");
        }

        return $package;
    }
}
