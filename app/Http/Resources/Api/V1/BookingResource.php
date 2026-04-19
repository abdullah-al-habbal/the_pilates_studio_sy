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
            'has_credits_remaining' => $this->has_credits_remaining,
            'can_deduct_credit' => $this->can_deduct_credit,
            'can_be_cancelled' => $this->can_be_cancelled,
            'is_exhausted' => $this->is_exhausted,
            'is_within_validity' => $this->is_within_validity,
            'credits_near_empty' => $this->credits_near_empty,
            'package' => new PackageResource($this->whenLoaded('package')),
        ];
    }
}
