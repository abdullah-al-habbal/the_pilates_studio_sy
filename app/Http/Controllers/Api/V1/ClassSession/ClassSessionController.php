<?php

// filePath: app/Http/Controllers/Api/V1/ClassSession/ClassSessionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\ClassSession;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClassSessionResource;
use App\Models\ClassSession;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClassSessionController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $sessions = ClassSession::query()
            ->with('class.instructor', 'class.primaryImage')
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(20);

        return $this->success(
            ClassSessionResource::collection($sessions)->response()->getData(true),
        );
    }

    public function show(int $id): JsonResponse
    {
        $session = ClassSession::with(
            'class.instructor',
            'class.category',
            'class.primaryImage',
        )->findOrFail($id);

        return $this->success(new ClassSessionResource($session));
    }
}
