<?php

// filePath: app/Http/Resources/Api/V1/AppSettingResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key'         => $this->key,
            'value'       => $this->value,
            'description' => $this->description,
        ];
    }
}
