<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientActivePackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->package?->getTranslation('name', app()->getLocale()),
            'source_type' => $this->resource->source_type,
            'total_credits' => $this->resource->total_credits,
            'remaining_credits' => $this->resource->remaining_credits,
            'expires_at' => $this->resource->expires_at?->toDateString(),
            'status' => $this->resource->status,
            'paid_amount' => $this->resource->paid_amount,
            'currency_id' => $this->resource->currency_id,
        ];
    }
}
