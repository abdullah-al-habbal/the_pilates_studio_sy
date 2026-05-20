<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Support\Operations\BookingPackageMapper;
use App\Support\Operations\ClientDisplayStatusResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'member_since' => $this->created_at?->format('M Y'),
            'is_active' => $this->isActive(),
            'status' => ClientDisplayStatusResolver::resolve($this->resource),
            'active_package' => BookingPackageMapper::toArray($this->activeCreditBooking),
            'frozen_package' => BookingPackageMapper::toArray($this->frozenCreditBooking),
            'activity_snapshot' => [
                'total_sessions_attended' => $this->bookingSessions()
                    ->where('booking_sessions.attendance_status', 'attended')
                    ->count(),
                'total_sessions_cancelled' => $this->bookingSessions()
                    ->where('booking_sessions.status', 'cancelled')
                    ->count(),
            ],
            'store_purchases' => $this->merchandiseOrders()
                ->with('merchandise')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($order) => [
                    'item_name' => $order->merchandise?->getTranslation('name', app()->getLocale()),
                    'quantity' => $order->quantity,
                    'total_price' => $order->paid_amount,
                    'currency_id' => $order->currency_id,
                    'ordered_at' => $order->ordered_at?->format('M d, Y'),
                ]),
        ];
    }
}
