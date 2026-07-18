<?php

// filePath: app/Http/Resources/Api/V1/AppSettingResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AppSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->value;

        if (($this->type ?? null) === 'image' && is_string($value) && filled($value)) {
            $value = Storage::disk('public')->url($value);
        }

        return [
            'key' => $this->key,
            'value' => $value,
            'description' => $this->description,
        ];
    }
}
