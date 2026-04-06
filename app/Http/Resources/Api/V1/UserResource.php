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
            'email_verified' => ! is_null($this->email_verified_at),
            'is_active' => $this->isActive(),
            'total_remaining_credits' => $this->total_remaining_credits,
            'active_booking' => $this->whenLoaded('activeCreditBooking', fn () => new BookingResource($this->activeCreditBooking)) 
                ?? ($this->relationLoaded('activeCreditBooking') === false ? new BookingResource($this->activeCreditBooking) : null),
        ];
    }
}
