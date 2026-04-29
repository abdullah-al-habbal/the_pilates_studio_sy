<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\MerchandiseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read MerchandiseOrder $resource
 */
class ClientStorePurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->resource->id,
            'item_name'    => $this->resource->merchandise?->getTranslation('name', app()->getLocale()),
            'quantity'     => $this->resource->quantity,
            'total_price'  => $this->resource->total_price,
            'ordered_at'   => $this->resource->ordered_at?->toDateString(),
        ];
    }
}
