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
            'blocking_package' => BookingPackageMapper::toArray($this->blockingActiveBooking),
            'activity_snapshot' => [
                'total_sessions_attended' => $this->bookingSessions()
                    ->where('booking_sessions.attendance_status', 'attended')
                    ->count(),
                'total_sessions_cancelled' => $this->bookingSessions()
                    ->where('booking_sessions.status', 'cancelled')
                    ->count(),
            ],
            'packages' => ClientBookingHistoryResource::collection(
                $this->bookings()
                    ->with('package')
                    ->withCount('bookingSessions')
                    ->latest('id')
                    ->limit(10)
                    ->get(),
            )->resolve(),
            'store_purchases' => $this->merchandiseOrders()
                ->with('merchandise')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($order) => [
                    'item_name' => $order->merchandise?->getTranslation('name', app()->getLocale())
                        ?? $this->snapshotName($order->merchandise_name_snapshot),
                    'quantity' => $order->quantity,
                    'total_price' => $order->paid_amount,
                    'currency_id' => $order->currency_id,
                    'ordered_at' => $order->ordered_at?->format('M d, Y'),
                ]),
        ];
    }

    private function snapshotName(?array $snapshot): ?string
    {
        if (empty($snapshot)) {
            return null;
        }

        return $snapshot[app()->getLocale()] ?? array_values($snapshot)[0] ?? null;
    }
}
