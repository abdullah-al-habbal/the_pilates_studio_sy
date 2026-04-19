<?php

// filePath: app/Http/Resources/Api/V1/BookingSessionResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'attendance_status' => $this->attendance_status?->value,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'attended_at' => $this->attended_at?->toISOString(),
            'is_cancelled' => $this->isCancelled(),
            'is_reserved' => $this->isReserved(),
            'is_attended' => $this->isAttended(),
            'is_missed' => $this->isMissed(),
            'can_cancel' => $this->can_cancel,
            'can_mark_attended' => $this->can_mark_attended,
            'can_mark_missed' => $this->can_mark_missed,
            'is_refundable' => $this->is_refundable,
            'attendance_required' => $this->attendance_required,
            'class_session' => new ClassSessionResource($this->whenLoaded('classSession')),
            'booking' => new BookingResource($this->whenLoaded('booking')),
        ];
    }
}
