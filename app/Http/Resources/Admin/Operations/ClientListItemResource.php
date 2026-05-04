<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeBooking = $this->resource->bookings
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->first();

        return [
            'id' => $this->resource->id,
            'fullname' => $this->resource->fullname,
            'phone_number' => $this->resource->phone_number,
            'is_active' => $this->resource->isActive(),
            'member_since' => $this->resource->created_at->toDateString(),
            'active_package' => $activeBooking
                ? [
                    'name' => $activeBooking->package?->getTranslation('name', app()->getLocale()),
                    'remaining_credits' => $activeBooking->remaining_credits,
                    'total_credits' => $activeBooking->total_credits,
                ]
                : null,
            'sessions_attended' => $this->resource->bookingSessions()
                ->where('attendance_status', 'attended')
                ->count(),
            'sessions_cancelled' => $this->resource->bookingSessions()
                ->where('booking_sessions.status', 'cancelled')
                ->count(),
        ];
    }
}
