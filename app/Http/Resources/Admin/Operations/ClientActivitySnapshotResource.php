<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class ClientActivitySnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_sessions_attended' => $this->resource->bookingSessions()
                ->where('attendance_status', 'attended')->count(),
            'total_sessions_cancelled' => $this->resource->bookingSessions()
                ->where('status', 'cancelled')->count(),
        ];
    }
}
