<?php

// filePath: app/Http/Controllers/Api/V1/Package/PackageController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Package;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PackageResource;
use App\Models\Package;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Packages')]
class PackageController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $package = Package::whereHas('bookings', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->findOrFail($id);

        return $this->success(new PackageResource($package));
    }
}
