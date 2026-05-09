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
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'member_since' => $this->created_at?->format('M Y'),
            'is_active' => $this->isActive(),
            'active_package' => $this->activeCreditBooking ? [
                'id' => $this->activeCreditBooking->id,
                'name' => $this->activeCreditBooking->package?->getTranslation('name', app()->getLocale()),
                'total_credits' => $this->activeCreditBooking->total_credits,
                'remaining_credits' => $this->activeCreditBooking->remaining_credits,
                'status' => $this->activeCreditBooking->status,
                'expires_at' => $this->activeCreditBooking->expires_at?->format('M d, Y'),
                'source_type' => $this->activeCreditBooking->source_type,
                'remaining_days' => $this->activeCreditBooking->expires_at
                    ? now()->diffInDays($this->activeCreditBooking->expires_at, false)
                    : null,
                'paid_amount' => $this->activeCreditBooking->paid_amount,
                'currency_id' => $this->activeCreditBooking->currency_id,
            ] : null,
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
                ->map(fn($order) => [
                    'item_name' => $order->merchandise?->getTranslation('name', app()->getLocale()),
                    'quantity' => $order->quantity,
                    'total_price' => $order->merchandise?->getPriceForCurrentCurrency() * $order->quantity,
                    'ordered_at' => $order->ordered_at?->format('M d, Y'),
                ]),
        ];
    }
}
