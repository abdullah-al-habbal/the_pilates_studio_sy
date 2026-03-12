<?php

// filePath: app/Http/Resources/Api/V1/LanguageResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'name'       => $this->name,
            'direction'  => $this->direction,
            'is_default' => $this->is_default,
        ];
    }
}
