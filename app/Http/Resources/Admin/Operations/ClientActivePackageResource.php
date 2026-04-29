<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Booking $resource
 */
class ClientActivePackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource->id,
            'package_name'      => $this->resource->package?->getTranslation('name', app()->getLocale()),
            'total_credits'     => $this->resource->total_credits,
            'remaining_credits' => $this->resource->remaining_credits,
            'expires_at'        => $this->resource->expires_at?->toDateString(),
            'activated_at'      => $this->resource->activated_at?->toDateString(),
            'status'            => $this->resource->status,
        ];
    }
}
