<?php

// filePath: app/Http/Resources/Api/V1/PackageResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'total_credits' => $this->total_credits,
            'price' => $this->price,
            'is_active' => $this->is_active,
        ];
    }
}
