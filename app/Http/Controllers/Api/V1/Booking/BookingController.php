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
use App\Http\Requests\Api\V1\Booking\CreateBookingRequest;
use App\Models\Package;
use App\Enums\Api\SuccessCodeEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $bookings = $this->bookingService->listUserBookings($request->user()->id, $filters);

        return $this->success(new BookingCollection($bookings));
    }

    #[Endpoint('Get booking by ID', description: 'Returns a booking by its ID.')]
    public function show(Request $request, int $id): JsonResponse
    {
        Log::info('BookingController@show called', ['user_id' => $request->user()?->id, 'id' => $id]);

        try {
            $booking = $this->bookingService->findByUser($request->user()->id, $id);
            return $this->success(new BookingResource($booking));
        } catch (\Throwable $e) {
            Log::error('BookingController@show exception', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    #[Endpoint('Create a new booking (purchase a package)')]
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $user = $request->user();
        $package = Package::findOrFail($request->package_id);

        $booking = $this->bookingService->createFromPackage($user, $package);

        return $this->created(
            new BookingResource($booking),
            SuccessCodeEnum::BOOKING_CREATED,
            'Booking created successfully.'
        );
    }
}
