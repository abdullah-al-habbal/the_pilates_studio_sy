<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class ClientListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->resource->id,
            'fullname'     => $this->resource->fullname,
            'phone_number' => $this->resource->phone_number,
            'is_active'    => $this->resource->isActive(),
        ];
    }
}
