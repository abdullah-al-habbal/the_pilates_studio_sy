<?php

// filePath: app/Http/Resources/Api/V1/UserSettingResource.php
declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'allow_notifications' => $this->allow_notifications,
            'fcm_token' => $this->when($request->user()?->id === $this->resource->user_id, $this->fcm_token),
            'preferred_locale' => $this->resolvedLocale(),
            'preferred_language' => LanguageResource::make($this->whenLoaded('preferredLanguage')),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
