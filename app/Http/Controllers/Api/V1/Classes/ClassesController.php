<?php

// filePath: app/Http/Controllers/Api/V1/Classes/ClassesController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Classes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClassesResource;
use App\Models\Classes;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClassesController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $classes = Classes::query()
            ->with('instructor', 'category', 'primaryImage')
            ->where('status', \App\Enums\ClassStatusEnum::ACTIVE)
            ->latest()
            ->paginate(20);

        return $this->success(
            ClassesResource::collection($classes)->response()->getData(true),
        );
    }

    public function show(int $id): JsonResponse
    {
        $class = Classes::with(
            'instructor',
            'category',
            'images',
            'recurrencePattern',
        )->findOrFail($id);

        return $this->success(new ClassesResource($class));
    }
}
