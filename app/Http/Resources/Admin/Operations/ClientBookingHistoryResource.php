<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Enums\BookingStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientBookingHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'package_name' => $this->resource->package?->getTranslation('name', app()->getLocale()),
            'status' => $this->resource->status,
            'total_credits' => $this->resource->total_credits,
            'remaining_credits' => $this->resource->remaining_credits,
            'purchased_at' => $this->resource->purchased_at?->toDateString(),
            'expires_at' => $this->resource->expires_at?->toDateString(),
            'sessions_count' => (int) ($this->resource->booking_sessions_count ?? 0),
            'can_edit' => ! in_array($this->resource->status, [BookingStatusEnum::CANCELLED, BookingStatusEnum::FROZEN], true),
            'created_at' => $this->resource->created_at->toDateString(),
        ];
    }
}
