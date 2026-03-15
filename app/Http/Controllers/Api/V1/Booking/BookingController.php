<?php
// filePath: app/Http/Controllers/Api/V1/Booking/BookingController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Booking;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\BookingCollection;
use App\Http\Resources\Api\V1\BookingResource;
use App\Services\Booking\BookingService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Bookings')]
class BookingController extends BaseApiController
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    #[Endpoint('List bookings', description: 'Returns a paginated list of user bookings.')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'per_page']);
        $bookings = $this->bookingService->listUserBookings($request->user(), $filters);

        return $this->success(new BookingCollection($bookings));
    }

    #[Endpoint('Get booking by ID', description: 'Returns a booking by its ID.')]
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = $this->bookingService->findUserBooking($request->user(), $id);
        return $this->success(new BookingResource($booking));
    }
}
