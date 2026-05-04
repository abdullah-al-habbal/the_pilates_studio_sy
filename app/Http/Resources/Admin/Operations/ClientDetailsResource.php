<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Enums\BookingStatusEnum;
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
            'member_since'  => $this->resource->created_at?->format('M Y'),
            'is_active'     => $this->resource->isActive(),
            'active_package' => $activeBooking ? [
                'id' => $activeBooking->id,
                'name' => $activeBooking->package?->getTranslation('name', app()->getLocale()),
                'total_credits' => $activeBooking->total_credits,
                'remaining_credits' => $activeBooking->remaining_credits,
                'status' => $activeBooking->status,
                'expires_at' => $activeBooking->expires_at?->format('M d, Y'),
                'source_type' => $activeBooking->source_type,
                'remaining_days' => $activeBooking->expires_at 
                    ? now()->diffInDays($activeBooking->expires_at, false) 
                    : null,
            ] : null,
            'activity_snapshot' => [
                'total_sessions_attended' => $this->resource->bookingSessions()
                    ->where('attendance_status', 'attended')
                    ->count(),
                'total_sessions_cancelled' => $this->resource->bookingSessions()
                    ->where('booking_sessions.status', 'cancelled')
                    ->count(),
            ],
            'store_purchases' => $this->resource->merchandiseOrders()
                ->with('merchandise')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($order) => [
                    'item_name' => $order->merchandise?->getTranslation('name', app()->getLocale()),
                    'quantity' => $order->quantity,
                    'total_price' => $order->merchandise?->getPriceForCurrentCurrency() * $order->quantity,
                    'ordered_at' => $order->ordered_at?->format('M d, Y'),
                ]),
        ];
    }
}
