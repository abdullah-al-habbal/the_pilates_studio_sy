<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeBooking = $this->resource->bookings
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where(fn($b) => is_null($b->expires_at) || $b->expires_at->isFuture())
            ->first();

        return [
            'id'            => $this->resource->id,
            'fullname'      => $this->resource->fullname,
            'phone_number'  => $this->resource->phone_number,
            'email'         => $this->resource->email,
            'member_since'  => $this->resource->created_at->toDateString(),
            'is_active'     => $this->resource->isActive(),
            
            'active_package' => $activeBooking 
                ? new ClientActivePackageResource($activeBooking) 
                : null,
            
            'booking_history' => ClientBookingHistoryResource::collection($this->resource->bookings),
            
            'store_purchases' => ClientStorePurchaseResource::collection($this->resource->merchandiseOrders),
            
            'total_sessions_attended' => new ClientSessionCountResource(
                $this->resource->bookingSessions()->where('attendance_status', 'attended')->count()
            ),
            
            'total_sessions_cancelled' => new ClientSessionCountResource(
                $this->resource->bookingSessions()->where('status', 'cancelled')->count()
            ),
        ];
    }
}
