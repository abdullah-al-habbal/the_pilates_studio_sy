<?php

// filePath: app/Http/Controllers/Api/V1/Classes/ClassesController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Classes;

use App\Enums\ClassStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClassesResource;
use App\Models\Classes;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Classes')]
class ClassesController extends Controller
{
    #[Endpoint('List classes', description: 'Returns a paginated list of active classes.')]
    public function index(): JsonResponse
    {
        $classes = Classes::query()
            ->with('instructor', 'category', 'primaryImage')
            ->where('status', ClassStatusEnum::ACTIVE)
            ->latest()
            ->paginate(20);

        return $this->success(
            ClassesResource::collection($classes)->response()->getData(true),
        );
    }

    #[Endpoint('Get class by ID', description: 'Returns a class by its ID.')]
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
