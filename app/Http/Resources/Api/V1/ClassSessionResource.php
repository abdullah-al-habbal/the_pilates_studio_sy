<?php

// filePath: app/Http/Resources/Api/V1/ClassSessionResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'total_spots' => $this->total_spots,
            'available_spots' => $this->available_spots,
            'is_full' => $this->isFull(),
            'status' => $this->status->value,
            'is_scheduled' => $this->isScheduled(),
            'class' => new ClassesResource($this->whenLoaded('class')),
        ];
    }
}
