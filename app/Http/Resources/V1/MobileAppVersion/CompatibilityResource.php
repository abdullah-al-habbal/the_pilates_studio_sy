<?php
// filePath: app/Http/Resources/V1/MobileAppVersion/CompatibilityResource.php

declare(strict_types=1);

namespace App\Http\Resources\V1\MobileAppVersion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompatibilityResource extends JsonResource
{
    private string $sessionId;

    public function __construct($resource, string $sessionId)
    {
        parent::__construct($resource);
        $this->sessionId = $sessionId;
    }

    public function toArray(Request $request): array
    {
        return [
            'session_id' => $this->sessionId,
            'update_required' => $this->resource['update_required'],
            'update_available' => $this->resource['update_available'],
            'message' => $this->resource['message'],
            'store_url' => $this->resource['store_url'],
            'min_version' => $this->resource['min_version'],
            'latest_version' => $this->resource['latest_version'],
        ];
    }
}
