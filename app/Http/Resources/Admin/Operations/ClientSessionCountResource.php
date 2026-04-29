<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientSessionCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'count' => $this->resource,
        ];
    }
}
