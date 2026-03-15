<?php
// filePath: app/Http/Controllers/Api/V1/Instructor/InstructorController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InstructorResource;
use App\Services\Instructor\InstructorService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Instructors')]
class InstructorController extends Controller
{
    public function __construct(
        private readonly InstructorService $instructorService
    ) {}

    #[Endpoint('Get instructor by ID', description: 'Returns an instructor and their active classes.')]
    public function show(int $id): JsonResponse
    {
        $instructor = $this->instructorService->getInstructorWithActiveClasses($id);

        return $this->success(new InstructorResource($instructor));
    }
}