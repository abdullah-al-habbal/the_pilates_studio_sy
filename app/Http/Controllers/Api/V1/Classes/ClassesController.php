<?php
// filePath: app/Http/Controllers/Api/V1/Classes/ClassesController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Classes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClassesResource;
use App\Services\Classes\ClassesService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Classes')]
class ClassesController extends Controller
{
    public function __construct(
        private readonly ClassesService $classesService
    ) {}

    #[Endpoint('List classes', description: 'Returns a paginated list of active classes.')]
    public function index(): JsonResponse
    {
        $classes = $this->classesService->listActiveClasses();

        return $this->success(
            ClassesResource::collection($classes)->response()->getData(true)
        );
    }

    #[Endpoint('Get class by ID', description: 'Returns a class by its ID.')]
    public function show(int $id): JsonResponse
    {
        $class = $this->classesService->getClassById($id);

        return $this->success(new ClassesResource($class));
    }
}
