<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\CenterMerchandise $resource
 */
class MerchandiseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'name'           => $this->resource->getTranslation('name', app()->getLocale()),
            'price'          => $this->resource->price,
            'stock_quantity' => $this->resource->stock_quantity,
            'category'       => $this->resource->category?->name,
        ];
    }
}
