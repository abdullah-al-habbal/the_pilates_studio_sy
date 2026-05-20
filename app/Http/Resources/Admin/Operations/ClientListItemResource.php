<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\User;
use App\Support\Operations\BookingPackageMapper;
use App\Support\Operations\ClientDisplayStatusResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        $activeBooking = $this->findActiveBooking($user);
        $frozenBooking = $this->findFrozenBooking($user);

        return [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'phone_number' => $user->phone_number,
            'is_active' => $user->isActive(),
            'status' => ClientDisplayStatusResolver::resolve($user),
            'member_since' => $user->created_at->toDateString(),
            'active_package' => BookingPackageMapper::toArray($activeBooking),
            'frozen_package' => BookingPackageMapper::toArray($frozenBooking),
            'sessions_attended' => $user->bookingSessions()
                ->where('attendance_status', 'attended')
                ->count(),
            'sessions_cancelled' => $user->bookingSessions()
                ->where('booking_sessions.status', 'cancelled')
                ->count(),
        ];
    }

    private function findActiveBooking(User $user): ?Booking
    {
        if ($user->relationLoaded('activeCreditBooking') && $user->activeCreditBooking) {
            return $user->activeCreditBooking;
        }

        return $user->bookings
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->first();
    }

    private function findFrozenBooking(User $user): ?Booking
    {
        if ($user->relationLoaded('frozenCreditBooking') && $user->frozenCreditBooking) {
            return $user->frozenCreditBooking;
        }

        return $user->bookings
            ->where('status', BookingStatusEnum::FROZEN)
            ->first();
    }
}
