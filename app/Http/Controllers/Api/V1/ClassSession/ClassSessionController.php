<?php

// filePath: app/Http/Controllers/Api/V1/ClassSession/ClassSessionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\ClassSession;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\ClassSession\QueryClassSessionsRequest;
use App\Http\Resources\Api\V1\ClassSessionResource;
use App\Services\ClassSession\ClassSessionService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Class Sessions')]
class ClassSessionController extends BaseApiController
{
    public function __construct(
        private readonly ClassSessionService $classSessionService
    ) {}

    #[Endpoint('List class sessions', description: 'Returns filtered upcoming class sessions.')]
    public function index(QueryClassSessionsRequest $request): JsonResponse
    {

        $sessions = $this->classSessionService->querySessions(
            date: $request->date,
            dateAfter: $request->date_after,
            dateBefore: $request->date_before,
            startAfter: $request->start_after,
            classId: $request->integer('class_id') ?: null,
            perPage: $request->integer('per_page') ?: 20
        );

        return $this->success(
            ClassSessionResource::collection($sessions)->response()->getData(true)
        );
    }

    #[Endpoint('Get class session by ID', description: 'Returns a class session by its ID.')]
    public function show(int $id): JsonResponse
    {
        $session = $this->classSessionService->getSessionById($id);

        return $this->success(new ClassSessionResource($session));
    }
}
