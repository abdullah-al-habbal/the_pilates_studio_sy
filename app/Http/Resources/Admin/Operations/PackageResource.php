<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Package $resource
 */
class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->resource->id,
            'name'          => $this->resource->getTranslation('name', app()->getLocale()),
            'total_credits' => $this->resource->total_credits,
            'validity_days' => $this->resource->validity_days,
            'price'         => $this->resource->price,
        ];
    }
}
