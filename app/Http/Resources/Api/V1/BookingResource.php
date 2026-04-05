<?php

// filePath: app/Http/Resources/Api/V1/BookingResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'total_credits' => $this->total_credits,
            'remaining_credits' => $this->remaining_credits,
            'used_credits' => $this->used_credits,
            'credits_usage_percentage' => $this->credits_usage_percentage,
            'credits_progress_color' => $this->credits_progress_color,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'package' => new PackageResource($this->whenLoaded('package')),
        ];
    }
}
