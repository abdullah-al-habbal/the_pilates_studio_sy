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
        // todo: make language Resource for better code
        return [
            'id'                 => $this->id,
            'allow_notifications' => $this->allow_notifications,
            'fcm_token'          => $this->fcm_token,
            'preferred_locale'   => $this->resolvedLocale(),
            'preferred_language' => $this->whenLoaded('preferredLanguage', fn() => [
                'id'        => $this->preferredLanguage->id,
                'code'      => $this->preferredLanguage->code,
                'name'      => $this->preferredLanguage->name,
                'direction' => $this->preferredLanguage->direction,
            ]),
            'updated_at'         => $this->updated_at->toISOString(),
        ];
    }
}
