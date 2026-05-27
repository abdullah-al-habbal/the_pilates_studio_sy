<?php

// filePath: app/Services/Package/PackageService.php

declare(strict_types=1);

namespace App\Services\Package;

use App\Models\Package;
use App\Repositories\Eloquent\Package\PackageEloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PackageService
{
    public function __construct(
        private readonly PackageEloquentRepository $repository
    ) {}

    public function getTopActivePackages(int $limit = 3): Collection
    {
        return $this->repository->getTopActivePackages($limit);
    }

    public function findById(int $id): Package
    {
        $package = $this->repository->findById($id);
        if (! $package) {
            throw new ModelNotFoundException("Package with ID {$id} not found.");
        }
        return $package;
    }

    public function hasActivePackage(): bool
    {
        return $this->repository->hasActivePackage();
    }

    public function getCheapestActivePackage(): ?Package
    {
        return $this->repository->getCheapestActivePackage();
    }

    public function getUserBookedPackage(int $userId, int $packageId): Package
    {
        $package = $this->repository->findUserBookedPackage($userId, $packageId);

        if (! $package) {
            throw new ModelNotFoundException("Package with ID {$packageId} not found or not booked by user.");
        }

        return $package;
    }

    public function findActiveWalkInPackage(): ?Package
    {
        return $this->repository->findActiveWalkInPackage();
    }

    public function createWalkInPackage(): Package
    {
        return $this->repository->createWalkInPackage();
    }
}
