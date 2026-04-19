<?php

// filePath: app/Http/Resources/Api/V1/UserResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'email_verified' => $this->is_verified,
            'is_deactivated' => $this->is_deactivated,
            'has_credits' => $this->has_credits,
            'can_book_new_package' => $this->can_book_new_package,
            'can_reserve_class' => $this->can_reserve_class,
            'has_active_booking' => $this->has_active_booking,
            'total_remaining_credits' => $this->total_remaining_credits,
            'active_booking' => $this->whenLoaded('activeCreditBooking', fn () => new BookingResource($this->activeCreditBooking))
                ?? ($this->relationLoaded('activeCreditBooking') === false ? new BookingResource($this->activeCreditBooking) : null),
        ];
    }
}
