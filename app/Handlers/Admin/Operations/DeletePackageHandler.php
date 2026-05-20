<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Package;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final readonly class DeletePackageHandler
{
    public function handle(int $packageId): void
    {
        $package = Package::find($packageId);

        if ($package === null) {
            throw new ModelNotFoundException("Package {$packageId} not found.");
        }

        if ($package->bookings()->exists()) {
            throw ValidationException::withMessages([
                'package' => 'Cannot delete a package that has existing bookings.',
            ]);
        }

        $package->delete();
    }
}
