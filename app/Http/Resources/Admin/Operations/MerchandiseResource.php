<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\CenterMerchandise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CenterMerchandise $resource
 */
class MerchandiseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'name'           => $this->resource->getTranslation('name', app()->getLocale()),
            'price'          => $this->resource->getPriceForCurrentCurrency(),
            'stock_quantity' => $this->resource->stock_quantity,
            'category'       => $this->resource->category?->name,
        ];
    }
}
