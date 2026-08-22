<?php

// filePath: app/Http/Resources/Api/V1/ClassesResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\WeekdayEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'about' => $this->about,
            'status' => $this->status->value,
            'is_active' => $this->isActive(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            // Readable day names, e.g. ["sunday","wednesday"]; null when the
            // class repeats on a fixed interval instead.
            'weekdays' => $this->hasWeekdaySchedule()
                ? array_map(fn (WeekdayEnum $day) => $day->value, $this->weekdayCases())
                : null,
            'total_spots' => $this->total_spots,
            'instructor' => new InstructorResource($this->whenLoaded('instructor')),
            'category' => new ClassCategoryResource($this->whenLoaded('category')),
            'primary_image' => new ClassImageResource($this->whenLoaded('primaryImage')),
            'images' => ClassImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
