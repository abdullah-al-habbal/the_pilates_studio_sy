<?php

// filePath: app/Http/Resources/Api/V1/NotificationResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => ! $this->isUnread(),
            'read_at' => $this->read_at?->toISOString(),
        ];
    }
}
