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
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'is_cancelled' => $this->isCancelled(),
            'is_reserved' => $this->isReserved(),
            'is_attended' => $this->isAttended(),
            'is_no_show' => $this->isNoShow(),
            'class_session' => new ClassSessionResource($this->whenLoaded('classSession')),
        ];
    }
}
