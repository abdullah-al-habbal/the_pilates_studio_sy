<?php

// filePath: app/Http/Controllers/Api/V1/Instructor/InstructorController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Enums\ClassStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InstructorResource;
use App\Models\Instructor;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Instructors')]
class InstructorController extends Controller
{
    #[Endpoint('Get instructor by ID', description: 'Returns an instructor and their active classes.')]
    public function show(int $id): JsonResponse
    {
        $instructor = Instructor::with([
            'classes' => fn ($query) => $query
                ->with('primaryImage', 'category')
                ->where('status', ClassStatusEnum::ACTIVE)
                ->latest(),
        ])->findOrFail($id);

        return $this->success(new InstructorResource($instructor));
    }
}
