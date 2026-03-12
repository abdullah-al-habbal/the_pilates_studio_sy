<?php

// filePath: app/Http/Controllers/Api/V1/BookingSession/BookingSessionController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\BookingSession;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingSessionResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingSessionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->bookingSessions()
            ->with('classSession.class.instructor', 'classSession.class.primaryImage')
            ->latest()
            ->paginate(20);

        return $this->success(
            BookingSessionResource::collection($sessions)->response()->getData(true),
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $session = $request->user()
            ->bookingSessions()
            ->with('classSession.class.instructor', 'classSession.class.primaryImage')
            ->findOrFail($id);

        return $this->success(new BookingSessionResource($session));
    }
}
