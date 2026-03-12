<?php

// filePath: app/Http/Controllers/Api/V1/Instructor/InstructorController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InstructorResource;
use App\Models\Instructor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{

    public function show(int $id): JsonResponse
    {
        $instructor = Instructor::with([
            'classes' => fn($query) => $query
                ->with('primaryImage', 'category')
                ->where('status', \App\Enums\ClassStatusEnum::ACTIVE)
                ->latest(),
        ])->findOrFail($id);

        return $this->success(new InstructorResource($instructor));
    }
}
