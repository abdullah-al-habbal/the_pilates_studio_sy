<?php

// filePath: app/Http/Controllers/Api/V1/Booking/BookingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Booking;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with('package')
            ->latest()
            ->paginate(20);

        return $this->success(
            BookingResource::collection($bookings)->response()->getData(true),
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $booking = $request->user()
            ->bookings()
            ->with('package')
            ->findOrFail($id);

        return $this->success(new BookingResource($booking));
    }
}
